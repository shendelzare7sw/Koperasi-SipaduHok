<x-layouts.app title="Katalog - Koperasi Sipaduhok">
    <section class="rounded-3xl bg-slate-900 px-6 py-10 text-white sm:px-10">
        <p class="text-sm font-bold uppercase tracking-widest text-orange-400">Toko Koperasi Sekolah</p>
        <h1 class="mt-3 max-w-2xl text-3xl font-black sm:text-5xl">Buku, alat tulis, dan atribut sekolah dalam satu katalog.</h1>
        <p class="mt-4 max-w-2xl text-slate-300">Pesan secara online, bayar melalui kanal digital, lalu pesanan diantar oleh Kurir Koperasi.</p>
    </section>

    <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Cari produk..." class="rounded-xl border border-slate-300 px-4 py-3">
        <select name="category" class="rounded-xl border border-slate-300 px-4 py-3">
            <option value="">Semua kategori</option>
            @foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach
        </select>
        <button class="rounded-xl bg-orange-500 px-5 py-3 font-bold text-slate-950">Cari</button>
    </form>

    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($products as $product)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <a href="{{ route('catalog.show', $product) }}" class="block">
                    @if($product->image_path)
                        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
                    @else
                        <div class="grid aspect-square w-full place-items-center bg-slate-100 text-xs font-bold uppercase text-slate-400">Foto produk</div>
                    @endif
                    <div class="p-4">
                        <p class="text-xs font-bold uppercase text-orange-600">{{ $categories[$product->category] ?? $product->category }}</p>
                        <h2 class="mt-1 line-clamp-2 font-bold text-slate-900">{{ $product->name }}</h2>
                        <p class="mt-3 font-black text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-slate-500">Stok: {{ $product->stock }}</p>
                    </div>
                </a>
            </article>
        @empty
            <p class="col-span-full rounded-2xl bg-white p-8 text-center text-slate-500">Produk belum tersedia.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</x-layouts.app>
