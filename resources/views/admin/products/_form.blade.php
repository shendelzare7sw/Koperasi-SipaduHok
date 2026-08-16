@csrf
@if(isset($product)) @method('PUT') @endif
@php($remainingImageSlots = isset($product) ? max(0, 5 - $product->images->count()) : 5)
<div class="grid gap-4 sm:grid-cols-2" x-data="{ selectedCategory: @js(old('category', $product->category ?? 'buku')) }">
    <label class="text-sm font-semibold sm:col-span-2">Nama produk<input name="name" value="{{ old('name', $product->name ?? '') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <label class="text-sm font-semibold">Kategori<select name="category" x-model="selectedCategory" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(old('category', $product->category ?? 'buku') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label x-cloak x-show="selectedCategory === 'lainnya'" class="text-sm font-semibold">Nama kategori tambahan<input name="custom_category" value="{{ old('custom_category', $product->custom_category ?? '') }}" x-bind:required="selectedCategory === 'lainnya'" maxlength="100" placeholder="Contoh: Perlengkapan Harian" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"><span class="mt-1 block text-xs font-normal text-slate-500">Nama ini akan tampil sebagai kategori produk.</span></label>
    <label class="text-sm font-semibold">Harga (Rp)<input name="price" type="text" inputmode="numeric" pattern="[0-9.]*" value="{{ old('price', $product->price ?? '') }}" placeholder="0" data-rupiah-input required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <label class="text-sm font-semibold">Stok<input name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
    <div x-data="{ previews: [], previewOpen: false, activePreview: null }" class="sm:col-span-2">
        <label class="text-sm font-semibold">Foto produk (tersisa {{ $remainingImageSlots }} slot)
            <input name="images[]" type="file" accept="image/*" multiple @disabled($remainingImageSlots === 0) @change="const selected = Array.from($event.target.files).slice(0, {{ $remainingImageSlots }}); const transfer = new DataTransfer(); selected.forEach(file => transfer.items.add(file)); $event.target.files = transfer.files; previews.forEach(item => URL.revokeObjectURL(item.url)); previews = selected.map(file => ({ name: file.name, url: URL.createObjectURL(file) }))" class="mt-1 block w-full rounded-xl border border-slate-300 p-2 text-sm disabled:cursor-not-allowed disabled:bg-slate-100">
            <span class="mt-1 block text-xs font-normal text-slate-500">Maksimal lima foto. Foto pertama menjadi foto utama. Klik pratinjau untuk memperbesar sebelum disimpan.</span>
        </label>
        <div x-cloak x-show="previews.length" class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
            <template x-for="preview in previews" :key="preview.url">
                <button type="button" @click="activePreview = preview.url; previewOpen = true" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100" :aria-label="'Perbesar ' + preview.name">
                    <img :src="preview.url" :alt="preview.name" class="aspect-square w-full object-cover">
                    <span class="absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100"><i class="fas fa-magnifying-glass-plus"></i></span>
                </button>
            </template>
        </div>
        <div x-cloak x-show="previewOpen" x-transition.opacity @keydown.escape.window="previewOpen = false" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/90 p-4" role="dialog" aria-modal="true">
            <button type="button" @click="previewOpen = false" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-900" aria-label="Tutup pratinjau"><i class="fas fa-xmark"></i></button>
            <img :src="activePreview" alt="Pratinjau foto produk" class="max-h-[90vh] max-w-full rounded-2xl object-contain">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-slate-300"> Tampilkan produk di katalog</label>
    <label class="text-sm font-semibold sm:col-span-2">Deskripsi<textarea name="description" rows="5" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('description', $product->description ?? '') }}</textarea></label>
</div>
<div class="mt-6 flex gap-3"><button class="rounded-xl bg-primary px-5 py-3 font-bold text-white transition hover:bg-secondary">Simpan Produk</button><a href="{{ route('admin.products.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 font-bold">Batal</a></div>
