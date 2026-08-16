<x-layouts.app title="Katalog - Koperasi Sipaduhok">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-10 text-white shadow-xl shadow-primary/15 sm:px-10">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-yellow/20 blur-2xl"></div>
        <p class="relative text-sm font-bold uppercase tracking-widest text-accent-yellow">Toko Koperasi Sekolah</p>
        <h1 class="relative mt-3 max-w-2xl text-3xl font-black sm:text-5xl">Buku, alat tulis, dan atribut sekolah dalam satu katalog.</h1>
        <p class="relative mt-4 max-w-2xl text-blue-50">Pesan secara online, bayar melalui kanal digital, lalu pesanan diantar oleh Kurir Koperasi.</p>
    </section>

    <form method="GET" class="mt-8 grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white p-4 lg:grid-cols-6">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nama produk..." class="col-span-2 rounded-xl border border-slate-300 px-4 py-3 lg:col-span-2">
        <select name="category" class="rounded-xl border border-slate-300 px-4 py-3">
            <option value="">Semua kategori</option>
            @foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach
        </select>
        <select name="rating" class="rounded-xl border border-slate-300 px-4 py-3"><option value="">Semua rating</option>@foreach([5,4,3,2,1] as $rating)<option value="{{ $rating }}" @selected((int) request('rating') === $rating)>Minimal {{ $rating }} bintang</option>@endforeach</select>
        <input name="min_price" type="text" inputmode="numeric" pattern="[0-9.]*" value="{{ request('min_price') }}" placeholder="Harga minimum" data-rupiah-input class="rounded-xl border border-slate-300 px-4 py-3">
        <input name="max_price" type="text" inputmode="numeric" pattern="[0-9.]*" value="{{ request('max_price') }}" placeholder="Harga maksimum" data-rupiah-input class="rounded-xl border border-slate-300 px-4 py-3">
        <select name="sort" class="col-span-2 rounded-xl border border-slate-300 px-4 py-3 lg:col-span-2"><option value="newest">Produk terbaru</option><option value="price_asc" @selected(request('sort') === 'price_asc')>Harga terendah</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Harga tertinggi</option><option value="rating" @selected(request('sort') === 'rating')>Rating terbaik</option><option value="stock" @selected(request('sort') === 'stock')>Stok terbanyak</option></select>
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white transition hover:bg-secondary">Terapkan</button>
        <a href="{{ route('catalog.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center font-bold text-slate-600 hover:border-primary hover:text-primary">Reset</a>
    </form>

    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" :categories="$categories" :wishlisted="in_array($product->id, $wishlistIds, true)" />
        @empty
            <p class="col-span-full rounded-2xl bg-white p-8 text-center text-slate-500">Produk belum tersedia.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</x-layouts.app>
