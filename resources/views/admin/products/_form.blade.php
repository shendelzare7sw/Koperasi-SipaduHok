@csrf
@if(isset($product)) @method('PUT') @endif
<div class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm font-semibold sm:col-span-2">Nama produk<input name="name" value="{{ old('name', $product->name ?? '') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <label class="text-sm font-semibold">Kategori<select name="category" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(old('category', $product->category ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm font-semibold">Harga (Rp)<input name="price" type="number" min="0" value="{{ old('price', $product->price ?? 0) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <label class="text-sm font-semibold">Stok<input name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <label class="text-sm font-semibold">Foto produk<input name="image" type="file" accept="image/*" class="mt-1 block w-full rounded-xl border border-slate-300 p-2 text-sm"></label>
    <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-slate-300"> Tampilkan produk di katalog</label>
    <label class="text-sm font-semibold sm:col-span-2">Deskripsi<textarea name="description" rows="5" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('description', $product->description ?? '') }}</textarea></label>
</div>
<div class="mt-6 flex gap-3"><button class="rounded-xl bg-orange-500 px-5 py-3 font-bold text-slate-950">Simpan Produk</button><a href="{{ route('admin.products.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 font-bold">Batal</a></div>
