<x-layouts.app title="Kelola Produk">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-black">Kelola Produk</h1>
            <p class="text-slate-500">Buku, alat tulis, dan atribut sekolah.</p>
        </div>
        <div class="grid gap-2 sm:flex">
            <a href="{{ route('admin.products.archived') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-bold text-slate-600 hover:border-primary hover:text-primary"><i class="fas fa-box-archive" aria-hidden="true"></i>Arsip Produk</a>
            <a href="{{ route('admin.products.import.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary bg-white px-4 py-3 text-center font-bold text-primary hover:bg-blue-50"><i class="fas fa-file-excel" aria-hidden="true"></i>Import Excel</a>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-center font-bold text-white hover:bg-secondary"><i class="fas fa-plus" aria-hidden="true"></i>Tambah Produk</a>
        </div>
    </div>

    <form method="GET" class="mt-6 flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Cari produk" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3">
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary">Cari</button>
    </form>

    <form id="bulk-active-products" method="POST" action="{{ route('admin.products.bulk-archive') }}" data-confirm="Semua produk yang ditandai akan hilang dari katalog dan dipindahkan ke arsip." data-confirm-title="Arsipkan produk terpilih?" data-confirm-icon="warning" data-confirm-button="Ya, arsipkan">@csrf</form>

    <div x-data="{ selected: [], pageIds: @js($products->pluck('id')->values()) }" class="mt-5">
        <div class="mb-3 flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-slate-700"><i class="fas fa-square-check mr-2 text-primary" aria-hidden="true"></i><span x-text="selected.length"></span> produk dipilih</p>
            <button form="bulk-active-products" :disabled="selected.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40"><i class="fas fa-box-archive" aria-hidden="true"></i>Arsipkan Terpilih</button>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-blue-50/70 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="w-12 p-4"><input type="checkbox" aria-label="Pilih semua produk pada halaman ini" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" :checked="pageIds.length > 0 && selected.length === pageIds.length" :indeterminate.prop="selected.length > 0 && selected.length < pageIds.length" @change="selected = $event.target.checked ? [...pageIds] : []"></th>
                        <th class="p-4">Produk</th><th class="p-4">Kategori</th><th class="p-4 text-right">Harga</th><th class="p-4 text-center">Stok</th><th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4"><input form="bulk-active-products" type="checkbox" name="product_ids[]" value="{{ $product->id }}" x-model.number="selected" aria-label="Pilih {{ $product->name }}" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"></td>
                            <td class="p-4"><p class="font-bold">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p></td>
                            <td class="p-4">{{ $product->categoryLabel() }}</td><td class="p-4 text-right">Rp {{ number_format($product->price, 0, ',', '.') }}</td><td class="p-4 text-center">{{ $product->stock }}</td>
                            <td class="p-4"><div class="flex justify-end gap-3"><a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-primary hover:text-secondary">Edit</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Produk {{ $product->name }} akan diarsipkan dan hilang dari katalog." data-confirm-title="Arsipkan produk?" data-confirm-icon="warning" data-confirm-button="Ya, arsipkan">@csrf @method('DELETE')<button class="font-bold text-red-600">Arsip</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">Belum ada produk yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.app>
