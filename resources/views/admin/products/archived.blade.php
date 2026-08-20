<x-layouts.app title="Arsip Produk">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Kelola produk</p><h1 class="mt-1 text-3xl font-black text-slate-900">Arsip Produk</h1><p class="mt-1 text-slate-500">Produk di arsip tidak tampil pada katalog dan dapat dipulihkan atau dihapus permanen.</p></div>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary bg-white px-4 py-3 font-bold text-primary hover:bg-blue-50"><i class="fas fa-boxes-stacked" aria-hidden="true"></i>Produk Aktif</a>
    </div>

    <form method="GET" class="mt-6 flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Cari produk dalam arsip" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3">
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary">Cari</button>
    </form>

    <form id="bulk-archived-products" method="POST" action="{{ route('admin.products.archived.bulk-action') }}" data-confirm="Tindakan akan diterapkan ke semua produk arsip yang ditandai. Hapus permanen tidak dapat dibatalkan." data-confirm-title="Proses produk terpilih?" data-confirm-icon="warning" data-confirm-button="Ya, lanjutkan">@csrf</form>

    <div x-data="{ selected: [], pageIds: @js($products->pluck('id')->values()) }" class="mt-5">
        <div class="mb-3 flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-slate-700"><i class="fas fa-square-check mr-2 text-primary" aria-hidden="true"></i><span x-text="selected.length"></span> produk dipilih</p>
            <div class="grid gap-2 sm:flex"><select form="bulk-archived-products" name="action" required class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 focus:border-primary focus:ring-primary"><option value="restore">Pulihkan ke produk aktif</option><option value="force_delete">Hapus permanen</option></select><button form="bulk-archived-products" :disabled="selected.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-40"><i class="fas fa-play" aria-hidden="true"></i>Proses Terpilih</button></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="w-12 p-4"><input type="checkbox" aria-label="Pilih semua produk arsip pada halaman ini" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" :checked="pageIds.length > 0 && selected.length === pageIds.length" :indeterminate.prop="selected.length > 0 && selected.length < pageIds.length" @change="selected = $event.target.checked ? [...pageIds] : []"></th><th class="p-4">Produk</th><th class="p-4">Kategori</th><th class="p-4 text-center">Foto</th><th class="p-4">Diarsipkan</th><th class="p-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4"><input form="bulk-archived-products" type="checkbox" name="product_ids[]" value="{{ $product->id }}" x-model.number="selected" aria-label="Pilih {{ $product->name }}" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"></td>
                            <td class="p-4"><p class="font-bold text-slate-900">{{ $product->name }}</p><p class="text-xs text-slate-500">Rp {{ number_format($product->price, 0, ',', '.') }} · Stok {{ $product->stock }}</p></td><td class="p-4">{{ $product->categoryLabel() }}</td><td class="p-4 text-center">{{ $product->images_count }}</td><td class="p-4 text-xs text-slate-500">{{ $product->deleted_at?->format('d/m/Y H:i') }}</td>
                            <td class="p-4"><div class="flex justify-end gap-2"><form method="POST" action="{{ route('admin.products.restore', $product->id) }}" data-confirm="Produk {{ $product->name }} akan dipulihkan dan kembali mengikuti status aktif sebelumnya." data-confirm-title="Pulihkan produk?" data-confirm-button="Ya, pulihkan">@csrf @method('PATCH')<button class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-primary hover:bg-blue-100"><i class="fas fa-rotate-left mr-1" aria-hidden="true"></i>Pulihkan</button></form><form method="POST" action="{{ route('admin.products.force-destroy', $product->id) }}" data-confirm="Produk {{ $product->name }}, galeri foto, wishlist, dan relasinya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus permanen?" data-confirm-icon="warning" data-confirm-button="Ya, hapus permanen">@csrf @method('DELETE')<button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100"><i class="fas fa-trash mr-1" aria-hidden="true"></i>Hapus</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-12 text-center"><i class="fas fa-box-open text-4xl text-slate-300" aria-hidden="true"></i><p class="mt-3 font-bold text-slate-700">Arsip produk kosong.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.app>
