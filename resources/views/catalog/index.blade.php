<x-layouts.app title="Katalog - Koperasi Sipaduhok">
    @php
        $categoryIcons = [
            'buku' => 'fa-book-open',
            'alat_tulis' => 'fa-pen-ruler',
            'atribut_sekolah' => 'fa-shirt',
            'lainnya' => 'fa-box-open',
        ];
        $sortOptions = [
            'newest' => 'Produk terbaru',
            'price_asc' => 'Harga terendah',
            'price_desc' => 'Harga tertinggi',
            'rating' => 'Rating terbaik',
            'stock' => 'Stok terbanyak',
        ];
        $activeSort = request('sort', 'newest');
        $hasFilters = request()->filled('category') || request()->filled('min_price') || request()->filled('max_price') || request()->filled('rating');
    @endphp

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-6 text-white shadow-lg shadow-primary/15 sm:px-10 sm:py-7">
        <div class="pointer-events-none absolute -right-14 -top-24 h-48 w-48 rounded-full bg-accent-yellow/20 blur-2xl"></div>
        <p class="relative text-xs font-bold uppercase tracking-widest text-accent-yellow">Toko Koperasi Sekolah</p>
        <h1 class="relative mt-2 max-w-3xl text-3xl font-black leading-tight sm:text-4xl">Buku, alat tulis, dan atribut sekolah dalam satu katalog.</h1>
        <p class="relative mt-2 max-w-2xl text-sm text-blue-50 sm:text-base">Belanja kebutuhan sekolah secara praktis dan diantar oleh Kurir Koperasi.</p>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form method="GET" action="{{ route('catalog.index') }}" class="flex gap-2" role="search">
            <label class="relative min-w-0 flex-1"><span class="sr-only">Cari produk</span><i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Cari buku, alat tulis, atau atribut..." class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></label>
            <button class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-primary text-white transition hover:bg-primary-dark sm:flex sm:w-auto sm:items-center sm:px-6"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><span class="ml-2 hidden font-bold sm:inline">Cari</span></button>
        </form>

        <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-4" aria-label="Kategori produk">
            @foreach($categories as $value => $label)
                <a href="{{ route('catalog.index', ['category' => $value]) }}" class="group flex min-w-0 flex-col items-center rounded-2xl border px-2 py-3 text-center transition hover:-translate-y-0.5 hover:border-primary hover:bg-blue-50 hover:shadow-md {{ request('category') === $value ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 text-slate-600' }}">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-blue-50 to-emerald-50 text-lg text-primary transition group-hover:scale-105 sm:h-14 sm:w-14 sm:text-xl"><i class="fas {{ $categoryIcons[$value] }}" aria-hidden="true"></i></span>
                    <span class="mt-2 line-clamp-2 text-[10px] font-black leading-tight sm:text-sm">{{ $label }}</span>
                    <span class="mt-1 hidden text-[10px] font-bold text-slate-400 sm:block">{{ $categoryCounts->get($value, 0) }} produk</span>
                </a>
            @endforeach
        </div>
    </section>

    @if($catalogMode)
        <section x-data="{ filterOpen: false, sortOpen: false }" class="mt-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-primary">Hasil Katalog</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-900">{{ request()->filled('search') ? 'Hasil untuk “'.request('search').'”' : 'Produk sesuai pilihan' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk.</p>
                </div>

                <div class="relative self-start sm:self-auto" @click.outside="sortOpen = false">
                    <button type="button" @click="sortOpen = ! sortOpen" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-primary" aria-haspopup="true" :aria-expanded="sortOpen"><i class="fas fa-arrow-down-wide-short text-primary" aria-hidden="true"></i>{{ $sortOptions[$activeSort] ?? $sortOptions['newest'] }}<i class="fas fa-chevron-down text-xs text-slate-400" aria-hidden="true"></i></button>
                    <div x-cloak x-show="sortOpen" x-transition class="absolute left-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl sm:left-auto sm:right-0">
                        @foreach($sortOptions as $value => $label)
                            <a href="{{ route('catalog.index', [...request()->except(['sort', 'page']), 'sort' => $value]) }}" class="block px-4 py-2.5 text-sm {{ $activeSort === $value ? 'bg-blue-50 font-bold text-primary' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($hasFilters)
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-bold text-slate-500">Filter aktif:</span>
                    @if(request()->filled('category'))<a href="{{ route('catalog.index', request()->except(['category', 'page'])) }}" class="rounded-full bg-blue-50 px-3 py-1.5 font-bold text-primary">{{ $categories[request('category')] ?? request('category') }} <i class="fas fa-xmark ml-1" aria-hidden="true"></i></a>@endif
                    @if(request()->filled('min_price'))<a href="{{ route('catalog.index', request()->except(['min_price', 'page'])) }}" class="rounded-full bg-blue-50 px-3 py-1.5 font-bold text-primary">Min Rp {{ number_format((int) str_replace('.', '', request('min_price')), 0, ',', '.') }} <i class="fas fa-xmark ml-1" aria-hidden="true"></i></a>@endif
                    @if(request()->filled('max_price'))<a href="{{ route('catalog.index', request()->except(['max_price', 'page'])) }}" class="rounded-full bg-blue-50 px-3 py-1.5 font-bold text-primary">Maks Rp {{ number_format((int) str_replace('.', '', request('max_price')), 0, ',', '.') }} <i class="fas fa-xmark ml-1" aria-hidden="true"></i></a>@endif
                    @if(request()->filled('rating'))<a href="{{ route('catalog.index', request()->except(['rating', 'page'])) }}" class="rounded-full bg-amber-50 px-3 py-1.5 font-bold text-amber-700">{{ request('rating') }}+ bintang <i class="fas fa-xmark ml-1" aria-hidden="true"></i></a>@endif
                    <a href="{{ route('catalog.index', request()->filled('search') ? ['search' => request('search')] : []) }}" class="ml-1 font-bold text-red-600 hover:underline">Hapus filter</a>
                </div>
            @endif

            <button type="button" data-mobile-filter-toggle @click="filterOpen = true" class="fixed bottom-5 left-4 z-40 inline-flex items-center gap-2 rounded-full border-2 border-white bg-primary px-5 py-3 text-sm font-black text-white shadow-[0_10px_30px_rgba(22,95,172,0.4)] transition active:scale-95 lg:hidden"><i class="fas fa-filter" aria-hidden="true"></i>Filter</button>

            <div x-cloak x-show="filterOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Filter produk">
                <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-sm" @click="filterOpen = false"></div>
                <aside x-show="filterOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative h-full w-[320px] max-w-[88vw] overflow-y-auto bg-white shadow-2xl">
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4"><div><p class="text-xs font-black uppercase tracking-widest text-primary">Saring katalog</p><h3 class="font-black text-slate-900">Filter Produk</h3></div><button type="button" @click="filterOpen = false" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-500" aria-label="Tutup filter"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                    <div class="p-5">@include('catalog._filters')</div>
                </aside>
            </div>

            <div class="mt-6 flex gap-7">
                <aside class="hidden w-72 shrink-0 lg:block"><div class="sticky top-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-4"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-filter" aria-hidden="true"></i></span><div><p class="text-xs font-black uppercase tracking-widest text-slate-400">Saring katalog</p><h3 class="font-black text-slate-900">Filter Produk</h3></div></div>@include('catalog._filters')</div></aside>
                <div class="min-w-0 flex-1">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                        @forelse($products as $product)
                            <x-product-card :product="$product" :categories="$categories" :wishlisted="in_array($product->id, $wishlistIds, true)" />
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><i class="fas fa-magnifying-glass text-4xl text-slate-300" aria-hidden="true"></i><h3 class="mt-4 font-black text-slate-800">Produk tidak ditemukan</h3><p class="mt-1 text-sm text-slate-500">Coba ubah kata pencarian atau filter produk.</p><a href="{{ route('catalog.index') }}" class="mt-5 inline-flex rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">Kembali ke katalog</a></div>
                        @endforelse
                    </div>
                    <div class="mt-8">{{ $products->links() }}</div>
                </div>
            </div>
        </section>
    @else
        <section class="mt-8">
            <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-primary">Pilihan terbaru</p><h2 class="mt-1 text-2xl font-black text-slate-900">Produk Koperasi</h2></div><p class="hidden text-sm text-slate-500 sm:block">Gunakan pencarian atau kategori untuk membuka filter lengkap.</p></div>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @forelse($products as $product)
                    <x-product-card :product="$product" :categories="$categories" :wishlisted="in_array($product->id, $wishlistIds, true)" />
                @empty
                    <p class="col-span-full rounded-2xl bg-white p-8 text-center text-slate-500">Produk belum tersedia.</p>
                @endforelse
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        </section>
    @endif
</x-layouts.app>
