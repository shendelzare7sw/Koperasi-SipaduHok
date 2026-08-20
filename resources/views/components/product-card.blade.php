@props(['product', 'categories' => [], 'wishlisted' => false])
@php
    $imagePath = $product->primaryImagePath();
@endphp

<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    @auth
        @unless(auth()->user()->isAdmin())
            <form method="POST" action="{{ $wishlisted ? route('wishlist.destroy', $product) : route('wishlist.store', $product) }}" class="absolute right-3 top-3 z-10" data-confirm="{{ $wishlisted ? 'Produk akan dihapus dari wishlist.' : 'Produk akan disimpan ke wishlist.' }}" data-confirm-title="{{ $wishlisted ? 'Hapus dari wishlist?' : 'Simpan ke wishlist?' }}" data-confirm-button="Ya, lanjutkan">
                @csrf
                @if($wishlisted) @method('DELETE') @endif
                <button class="grid h-9 w-9 place-items-center rounded-full bg-white/95 shadow-md transition hover:scale-105 {{ $wishlisted ? 'text-red-500' : 'text-slate-400 hover:text-red-500' }}" aria-label="{{ $wishlisted ? 'Hapus '.$product->name.' dari wishlist' : 'Simpan '.$product->name.' ke wishlist' }}">
                    <i class="{{ $wishlisted ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                </button>
            </form>
        @endunless
    @endauth
    <a href="{{ route('catalog.show', $product) }}" class="relative block overflow-hidden bg-slate-100">
        @if($imagePath)
            <img src="{{ Storage::disk('public')->url($imagePath) }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="grid aspect-square w-full place-items-center px-4 text-center text-xs font-extrabold uppercase tracking-wide text-slate-400">Foto produk belum tersedia</div>
        @endif
        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-extrabold text-primary shadow-sm">
            {{ $product->categoryLabel() }}
        </span>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('catalog.show', $product) }}" class="line-clamp-2 min-h-12 font-extrabold text-slate-900 transition hover:text-primary">{{ $product->name }}</a>
        <p class="mt-2 text-lg font-black text-slate-950">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
        @if(($product->reviews_count ?? 0) > 0)
            <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-slate-500"><i class="fas fa-star text-accent-yellow" aria-hidden="true"></i>{{ number_format((float) $product->reviews_avg_rating, 1, ',', '.') }} <span class="text-slate-400">({{ $product->reviews_count }})</span></p>
        @endif
        <p class="mt-1 text-xs font-medium {{ $product->stock > 0 ? 'text-emerald-700' : 'text-red-600' }}">
            {{ $product->stock > 0 ? 'Stok '.$product->stock : 'Stok habis' }}
        </p>

        <div class="mt-auto pt-4">
            @auth
                @if(! auth()->user()->isAdmin())
                    <div class="grid grid-cols-[1fr_auto] gap-2">
                        <form method="POST" action="{{ route('checkout.buy-now', $product) }}">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button data-product-buy-now @disabled($product->stock < 1 || $product->price < 1) class="w-full rounded-xl bg-primary px-3 py-2.5 text-xs font-extrabold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-40">Beli Langsung</button>
                        </form>
                        <form method="POST" action="{{ route('cart.store', $product) }}" data-add-to-cart>
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button data-product-add-cart @disabled($product->stock < 1 || $product->price < 1) class="grid h-10 w-10 place-items-center rounded-xl border border-primary text-primary transition hover:border-secondary hover:bg-blue-50 hover:text-secondary disabled:cursor-not-allowed disabled:opacity-40" aria-label="Masukkan {{ $product->name }} ke keranjang" title="Masukkan ke keranjang"><i class="fas fa-cart-plus" aria-hidden="true"></i></button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-xl border border-slate-300 px-3 py-2.5 text-center text-xs font-extrabold text-slate-700 hover:border-primary hover:text-primary">Kelola produk</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="block rounded-xl bg-primary px-3 py-2.5 text-center text-xs font-extrabold text-white hover:bg-secondary">Masuk untuk membeli</a>
            @endauth
        </div>
    </div>
</article>
