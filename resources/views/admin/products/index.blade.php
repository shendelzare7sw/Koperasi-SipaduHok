<x-layouts.app title="Kelola Produk">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-black">Kelola Produk</h1>
            <p class="text-slate-500">Buku, alat tulis, dan atribut sekolah.</p>
        </div>
        <div class="grid gap-2 sm:flex">
            <a href="{{ route('admin.products.import.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary bg-white px-4 py-3 text-center font-bold text-primary hover:bg-blue-50">
                <i class="fas fa-file-excel" aria-hidden="true"></i>
                Import Excel
            </a>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-center font-bold text-white hover:bg-secondary">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Tambah Produk
            </a>
        </div>
    </div>

    <form method="GET" class="mt-6 flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Cari produk" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3">
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary">Cari</button>
    </form>

    <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-blue-50/70 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-4">Produk</th>
                    <th class="p-4">Kategori</th>
                    <th class="p-4 text-right">Harga</th>
                    <th class="p-4 text-center">Stok</th>
                    <th class="p-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr>
                        <td class="p-4">
                            <p class="font-bold">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                        </td>
                        <td class="p-4">{{ App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}</td>
                        <td class="p-4 text-right">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td class="p-4 text-center">{{ $product->stock }}</td>
                        <td class="p-4">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-primary hover:text-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Produk {{ $product->name }} akan diarsipkan dan hilang dari katalog." data-confirm-title="Arsipkan produk?" data-confirm-icon="warning" data-confirm-button="Ya, arsipkan">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-bold text-red-600">Arsip</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-slate-500">Belum ada produk yang sesuai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.app>
