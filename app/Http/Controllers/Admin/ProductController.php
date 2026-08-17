<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function archived(Request $request): View
    {
        $products = Product::onlyTrashed()
            ->withCount('images')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.archived', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.create', ['categories' => Product::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        unset($validated['images']);
        $product = Product::create($validated);
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', ['product' => $product->load('images'), 'categories' => Product::CATEGORIES]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        if (count($request->file('images', [])) + $product->images()->count() > 5) {
            throw ValidationException::withMessages([
                'images' => 'Maksimal lima foto untuk setiap produk.',
            ]);
        }

        $validated['slug'] = $this->uniqueSlug($validated['name'], $product);
        $validated['is_active'] = $request->boolean('is_active');

        unset($validated['images']);
        $product->update($validated);
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->cartItems()->delete();
        $product->delete();

        return back()->with('success', 'Produk dinonaktifkan dan dipindahkan ke arsip.');
    }

    public function restore(int $product): RedirectResponse
    {
        $archivedProduct = Product::onlyTrashed()->findOrFail($product);
        $archivedProduct->restore();

        return back()->with('success', "Produk {$archivedProduct->name} berhasil dipulihkan.");
    }

    public function forceDestroy(int $product): RedirectResponse
    {
        $archivedProduct = Product::onlyTrashed()->with('images')->findOrFail($product);
        $imagePaths = $archivedProduct->images->pluck('image_path')
            ->push($archivedProduct->image_path)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $productName = $archivedProduct->name;

        $archivedProduct->forceDelete();
        Storage::disk('public')->delete($imagePaths);

        return back()->with('success', "Produk {$productName} dan seluruh fotonya dihapus permanen.");
    }

    public function destroyImage(Product $product, ProductImage $productImage): RedirectResponse
    {
        abort_unless($productImage->product_id === $product->id, 404);

        $deletedPath = $productImage->image_path;
        Storage::disk('public')->delete($deletedPath);
        $productImage->delete();

        if ($product->image_path === $deletedPath) {
            $replacement = $product->images()->first();
            $replacement?->update(['is_primary' => true]);
            $product->update(['image_path' => $replacement?->image_path]);
        }

        return back()->with('success', 'Foto produk berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validateProduct(Request $request): array
    {
        $request->merge([
            'price' => $this->normaliseCurrency($request->input('price')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'custom_category' => ['nullable', 'required_if:category,lainnya', 'string', 'max:100'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'custom_category.required_if' => 'Tuliskan nama kategori tambahan untuk pilihan Lainnya.',
            'custom_category.max' => 'Nama kategori tambahan maksimal 100 karakter.',
            'images.*.image' => 'Foto produk harus berupa gambar yang valid.',
            'images.*.mimes' => 'Foto produk hanya mendukung JPG, JPEG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran setiap foto produk maksimal 3 MB.',
        ]);

        $validated['custom_category'] = $validated['category'] === 'lainnya'
            ? trim($validated['custom_category'])
            : null;

        return $validated;
    }

    private function normaliseCurrency(mixed $value): mixed
    {
        return is_string($value) ? str_replace('.', '', trim($value)) : $value;
    }

    private function storeImages(Request $request, Product $product): void
    {
        $files = $request->file('images', []);

        if (count($files) + $product->images()->count() > 5) {
            throw ValidationException::withMessages([
                'images' => 'Maksimal lima foto untuk setiap produk.',
            ]);
        }

        foreach ($files as $file) {
            $path = $file->store('products', 'public');
            $isPrimary = ! $product->images()->exists();
            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => $product->images()->count(),
            ]);

            if ($isPrimary) {
                $product->update(['image_path' => $path]);
            }
        }
    }

    private function uniqueSlug(string $name, ?Product $ignoredProduct = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)
            ->when($ignoredProduct, fn ($query) => $query->whereKeyNot($ignoredProduct->id))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
