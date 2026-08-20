<x-layouts.app title="{{ $product->name }} - Toko Sipaduhok">
    <div class="grid gap-8 lg:grid-cols-2">
        @php
            $gallery = $product->images->pluck('image_path');
            if ($gallery->isEmpty() && $product->image_path) $gallery = collect([$product->image_path]);
            $galleryUrls = $gallery->map(fn ($path) => Storage::disk('public')->url($path))->values();
        @endphp
        <div x-data="{ activeImage: @js($galleryUrls->first()), lightbox: false }">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white">
                @if($galleryUrls->isNotEmpty())
                    <button type="button" @click="lightbox = true" class="block w-full cursor-zoom-in" aria-label="Perbesar foto produk"><img :src="activeImage" alt="{{ $product->name }}" class="aspect-square w-full object-cover"></button>
                    <span class="absolute bottom-3 right-3 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-bold text-white"><i class="fas fa-magnifying-glass-plus mr-1"></i> Perbesar</span>
                @else
                    <div class="grid aspect-square place-items-center bg-slate-100 font-bold text-slate-400">Foto produk belum tersedia</div>
                @endif
            </div>
            @if($galleryUrls->count() > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach($galleryUrls as $url)
                        <button type="button" @click="activeImage = @js($url)" class="overflow-hidden rounded-xl border-2 bg-white" :class="activeImage === @js($url) ? 'border-primary' : 'border-transparent hover:border-primary/40'"><img src="{{ $url }}" alt="Foto {{ $product->name }} {{ $loop->iteration }}" class="aspect-square w-full object-cover"></button>
                    @endforeach
                </div>
            @endif
            <div x-cloak x-show="lightbox" x-transition.opacity @keydown.escape.window="lightbox = false" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/90 p-4" role="dialog" aria-modal="true">
                <button type="button" @click="lightbox = false" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-900" aria-label="Tutup foto"><i class="fas fa-xmark"></i></button>
                <img :src="activeImage" alt="{{ $product->name }}" class="max-h-[90vh] max-w-full rounded-2xl object-contain">
            </div>
        </div>
        <div class="lg:py-8">
            <div class="flex items-center justify-between gap-4">
                <p class="font-bold uppercase tracking-wide text-primary">{{ $product->categoryLabel() }}</p>
                @auth
                    @unless(auth()->user()->isAdmin())
                        <form method="POST" action="{{ $isWishlisted ? route('wishlist.destroy', $product) : route('wishlist.store', $product) }}" data-confirm="{{ $isWishlisted ? 'Produk akan dihapus dari wishlist.' : 'Produk akan disimpan ke wishlist.' }}" data-confirm-title="{{ $isWishlisted ? 'Hapus dari wishlist?' : 'Simpan ke wishlist?' }}" data-confirm-button="Ya, lanjutkan">
                            @csrf @if($isWishlisted) @method('DELETE') @endif
                            <button class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-bold {{ $isWishlisted ? 'border-red-200 bg-red-50 text-red-600' : 'border-slate-300 text-slate-600 hover:border-red-200 hover:text-red-500' }}"><i class="{{ $isWishlisted ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>{{ $isWishlisted ? 'Tersimpan' : 'Simpan' }}</button>
                        </form>
                    @endunless
                @endauth
            </div>
            <h1 class="mt-2 text-3xl font-black text-slate-900 sm:text-4xl">{{ $product->name }}</h1>
            @if($product->reviews_count > 0)
                <div class="mt-3 flex items-center gap-2 text-sm"><span class="font-extrabold text-slate-900">{{ number_format((float) $product->reviews_avg_rating, 1, ',', '.') }}</span><span class="text-accent-yellow">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= round($product->reviews_avg_rating) ? 'fas' : 'far' }} fa-star"></i>@endfor</span><span class="text-slate-500">{{ $product->reviews_count }} ulasan terverifikasi</span></div>
            @endif
            <p class="mt-4 text-2xl font-black">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="mt-2 text-sm text-slate-500">Stok tersedia: {{ $product->stock }}</p>
            <div class="mt-6 whitespace-pre-line leading-7 text-slate-600">{{ $product->description ?: 'Belum ada deskripsi.' }}</div>
            @auth
                @if(! auth()->user()->isAdmin())
                    <div x-data="{ quantity: 1 }" class="mt-8">
                        <label class="block text-sm font-bold text-slate-700">Jumlah
                            <input x-model.number="quantity" type="number" min="1" max="{{ $product->stock }}" class="mt-2 w-24 rounded-xl border border-slate-300 px-4 py-3">
                        </label>
                        <div class="mt-3 grid grid-cols-[1fr_auto] gap-3">
                            <form method="POST" action="{{ route('checkout.buy-now', $product) }}">
                                @csrf
                                <input type="hidden" name="quantity" :value="quantity" value="1">
                                <button @disabled($product->stock < 1 || $product->price < 1) class="w-full rounded-xl bg-primary px-5 py-3 font-extrabold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-50">Beli Langsung</button>
                            </form>
                            <form method="POST" action="{{ route('cart.store', $product) }}" data-add-to-cart>
                                @csrf
                                <input type="hidden" name="quantity" :value="quantity" value="1">
                                <button @disabled($product->stock < 1 || $product->price < 1) class="grid h-12 w-12 place-items-center rounded-xl border-2 border-primary text-primary transition hover:border-secondary hover:bg-blue-50 hover:text-secondary disabled:cursor-not-allowed disabled:opacity-50" aria-label="Masukkan produk ke keranjang" title="Masukkan ke keranjang"><i class="fas fa-cart-plus" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                        Anda sedang masuk sebagai admin. Gunakan <a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-primary hover:text-secondary">menu kelola produk</a> untuk mengubah produk ini.
                    </div>
                @endif
            @else
                <a href="{{ route('login') }}" class="mt-8 block rounded-xl bg-primary px-5 py-3 text-center font-bold text-white hover:bg-secondary">Masuk untuk Belanja</a>
            @endauth
        </div>
    </div>

    <section class="mt-12 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengalaman pembeli</p><h2 class="mt-1 text-2xl font-black text-slate-900">Ulasan Produk</h2></div>
            @if($product->reviews_count > 0)<div class="flex items-center gap-2"><span class="text-3xl font-black text-slate-900">{{ number_format((float) $product->reviews_avg_rating, 1, ',', '.') }}</span><div><div class="text-sm text-accent-yellow">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= round($product->reviews_avg_rating) ? 'fas' : 'far' }} fa-star"></i>@endfor</div><p class="text-xs text-slate-500">dari {{ $product->reviews_count }} ulasan</p></div></div>@endif
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse($product->reviews->take(6) as $review)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-extrabold text-slate-900">{{ $review->buyer->name }}</p><p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-secondary"><i class="fas fa-circle-check mr-1"></i>Pembelian terverifikasi</p></div><div class="text-xs text-accent-yellow">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</div></div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $review->comment ?: 'Pembeli memberikan penilaian tanpa komentar.' }}</p>
                    @if($review->admin_reply)<div class="mt-4 rounded-xl border-l-4 border-primary bg-white p-4"><p class="text-xs font-extrabold uppercase tracking-wide text-primary">Balasan Toko</p><p class="mt-1 text-sm leading-6 text-slate-600">{{ $review->admin_reply }}</p></div>@endif
                    <time class="mt-3 block text-xs text-slate-400">{{ $review->created_at->translatedFormat('d F Y') }}</time>
                </article>
            @empty
                <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center"><i class="far fa-star text-3xl text-slate-300"></i><p class="mt-3 text-sm text-slate-500">Belum ada ulasan untuk produk ini.</p></div>
            @endforelse
        </div>
    </section>

    @if($relatedProducts->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-end justify-between gap-4"><div><p class="text-sm font-extrabold uppercase tracking-widest text-primary">Kategori yang sama</p><h2 class="mt-1 text-2xl font-black text-slate-900">Produk Terkait</h2></div><a href="{{ route('catalog.index', ['category' => $product->category]) }}" class="text-sm font-bold text-primary hover:text-secondary">Lihat semua</a></div>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">@foreach($relatedProducts as $relatedProduct)<x-product-card :product="$relatedProduct" :categories="App\Models\Product::CATEGORIES" :wishlisted="in_array($relatedProduct->id, $wishlistIds, true)" />@endforeach</div>
        </section>
    @endif
</x-layouts.app>
