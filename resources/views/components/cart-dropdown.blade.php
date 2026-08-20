@props(['cartCount' => 0])

<div
    x-data="{
        cartOpen: false,
        loading: false,
        error: '',
        items: [],
        subtotal: 0,
        remainingItems: 0,
        currency(value) { return new Intl.NumberFormat('id-ID').format(Number(value) || 0) },
        async loadCart() {
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch(@js(route('cart.summary')), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (! response.ok) throw new Error('Ringkasan keranjang tidak dapat dimuat.');
                const payload = await response.json();
                this.items = payload.items || [];
                this.subtotal = payload.subtotal || 0;
                this.remainingItems = payload.remaining_items || 0;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        toggleCart() {
            this.cartOpen = ! this.cartOpen;
            if (this.cartOpen) this.loadCart();
        }
    }"
    @cart-updated.window="if (cartOpen) loadCart()"
    class="relative"
    data-desktop-cart-dropdown
>
    <button type="button" @click="toggleCart()" data-header-cart class="relative grid h-10 w-10 place-items-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/20" aria-label="Buka keranjang, {{ $cartCount }} produk" :aria-expanded="cartOpen">
        <i class="fas fa-cart-shopping" aria-hidden="true"></i>
        <span data-cart-count class="absolute -right-1 -top-1 h-5 min-w-5 place-items-center rounded-full bg-accent-yellow px-1 text-[10px] font-black text-slate-950 ring-2 ring-primary {{ $cartCount > 0 ? 'grid' : 'hidden' }}">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
    </button>

    <div x-cloak x-show="cartOpen" x-transition.origin.top.right @click.outside="cartOpen = false" class="absolute right-0 mt-3 w-[25rem] overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div><p class="font-extrabold text-slate-900">Keranjang</p><p class="text-xs text-slate-500"><span data-cart-count-number>{{ $cartCount }}</span> produk dipilih</p></div>
            <a href="{{ route('cart.index') }}" class="text-xs font-bold text-primary hover:text-secondary">Lihat semua</a>
        </div>

        <div class="relative max-h-96 overflow-y-auto">
            <div x-show="loading" class="grid min-h-32 place-items-center text-sm text-slate-500"><span><i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>Memuat keranjang...</span></div>
            <div x-show="! loading && ! error && items.length" class="divide-y divide-slate-100">
                <template x-for="item in items" :key="item.id">
                    <a :href="item.product_url" class="flex gap-3 px-4 py-3 transition hover:bg-blue-50">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-100 text-slate-300">
                            <img x-show="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover">
                            <i x-show="! item.image_url" class="fas fa-image" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-extrabold text-slate-900" x-text="item.name"></span><span class="mt-1 block text-xs text-slate-500"><span x-text="item.quantity"></span> × Rp <span x-text="currency(item.price)"></span></span></span>
                        <span class="shrink-0 text-sm font-black text-slate-900">Rp <span x-text="currency(item.subtotal)"></span></span>
                    </a>
                </template>
                <p x-show="remainingItems > 0" class="px-4 py-2 text-center text-xs font-bold text-slate-500">+<span x-text="remainingItems"></span> produk lainnya</p>
            </div>
            <div x-show="! loading && error" class="px-6 py-8 text-center"><i class="fas fa-triangle-exclamation text-2xl text-amber-500" aria-hidden="true"></i><p class="mt-3 text-sm font-bold text-slate-700" x-text="error"></p><button type="button" @click="loadCart()" class="mt-2 text-xs font-bold text-primary">Coba lagi</button></div>
            <div x-show="! loading && ! error && ! items.length" class="px-6 py-10 text-center"><i class="fas fa-cart-shopping text-3xl text-slate-300" aria-hidden="true"></i><p class="mt-3 text-sm font-bold text-slate-600">Keranjang masih kosong</p><a href="{{ route('catalog.index') }}" class="mt-2 inline-block text-xs font-bold text-primary">Mulai belanja</a></div>
        </div>

        <div x-show="! loading && ! error && items.length" class="border-t border-slate-100 p-4">
            <div class="mb-3 flex items-center justify-between text-sm"><span class="text-slate-500">Subtotal</span><strong class="text-base text-slate-900">Rp <span x-text="currency(subtotal)"></span></strong></div>
            <div class="grid grid-cols-2 gap-2"><a href="{{ route('cart.index') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Keranjang</a><a href="{{ route('checkout.create') }}" class="rounded-xl bg-primary px-3 py-2.5 text-center text-sm font-bold text-white hover:bg-secondary">Checkout</a></div>
        </div>
    </div>
</div>
