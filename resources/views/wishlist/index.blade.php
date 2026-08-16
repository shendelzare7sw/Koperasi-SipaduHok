<x-layouts.app title="Wishlist - Koperasi Sipaduhok">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pilihan tersimpan</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Wishlist Saya</h1>
            <p class="mt-2 text-sm text-slate-500">Simpan kebutuhan sekolah yang ingin dibeli nanti.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="rounded-xl bg-primary px-5 py-3 text-center text-sm font-bold text-white hover:bg-secondary"><i class="fas fa-store mr-2" aria-hidden="true"></i>Lihat Katalog</a>
    </div>

    <div class="mt-7 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" :categories="$categories" :wishlisted="true" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-xl text-primary"><i class="fas fa-heart" aria-hidden="true"></i></span>
                <h2 class="mt-4 font-extrabold text-slate-900">Wishlist masih kosong</h2>
                <p class="mt-1 text-sm text-slate-500">Tekan ikon hati pada produk yang ingin disimpan.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</x-layouts.app>
