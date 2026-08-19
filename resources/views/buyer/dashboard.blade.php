<x-layouts.app title="Dashboard Pembeli - Toko Sipaduhok">
    @php
        $identityStatus = $identityVerification?->status ?? 'not_submitted';
        $identityVerified = $identityStatus === App\Models\IdentityVerification::STATUS_VERIFIED;
    @endphp
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-8 text-white shadow-xl shadow-primary/15 sm:px-10 sm:py-10">
        <div class="pointer-events-none absolute -right-12 -top-16 h-52 w-52 rounded-full bg-accent-yellow/20 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-accent-yellow">Dashboard Pembeli</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">Halo, {{ auth()->user()->name }}</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-blue-50">Pantau pesanan, lanjutkan pembayaran, dan temukan kebutuhan sekolah dari satu halaman.</p>
            </div>
            <a href="{{ $identityVerified ? route('catalog.index') : route('account.identity.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900">
                <i class="fas {{ $identityVerified ? 'fa-store' : 'fa-id-card' }}" aria-hidden="true"></i> {{ $identityVerified ? 'Belanja Sekarang' : 'Verifikasi KTP' }}
            </a>
        </div>
    </section>

    @unless($identityVerified)
        <a href="{{ route('account.identity.edit') }}" class="mt-5 flex flex-col gap-3 rounded-2xl border p-4 transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between {{ $identityStatus === 'rejected' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }}">
            <span class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $identityStatus === 'rejected' ? 'bg-red-600' : 'bg-amber-500' }} text-white"><i class="fas {{ $identityStatus === 'pending' ? 'fa-clock' : ($identityStatus === 'rejected' ? 'fa-triangle-exclamation' : 'fa-id-card') }}" aria-hidden="true"></i></span><span><strong class="block text-slate-900">{{ $identityStatus === 'pending' ? 'KTP sedang ditinjau admin' : ($identityStatus === 'rejected' ? 'Verifikasi KTP perlu diperbaiki' : 'Verifikasi KTP diperlukan') }}</strong><span class="mt-1 block text-xs leading-5 text-slate-600">Checkout baru dapat digunakan setelah dokumen identitas disetujui admin toko.</span></span></span>
            <span class="shrink-0 text-sm font-extrabold text-primary">Buka verifikasi <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></span>
        </a>
    @endunless

    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a href="{{ route('cart.index') }}" class="rounded-2xl border border-blue-100 bg-white p-5 transition hover:-translate-y-0.5 hover:border-primary hover:shadow-lg"><div class="flex items-center justify-between"><p class="text-sm text-slate-500">Isi Keranjang</p><i class="fas fa-cart-shopping text-primary" aria-hidden="true"></i></div><p class="mt-2 text-3xl font-black text-slate-900">{{ $cartItemCount }}</p><p class="mt-2 text-xs font-semibold text-primary">Periksa keranjang →</p></a>
        <a href="{{ route('orders.index') }}" class="rounded-2xl border border-blue-100 bg-white p-5 transition hover:-translate-y-0.5 hover:border-primary hover:shadow-lg"><div class="flex items-center justify-between"><p class="text-sm text-slate-500">Menunggu Bayar</p><i class="fas fa-wallet text-primary" aria-hidden="true"></i></div><p class="mt-2 text-3xl font-black text-slate-900">{{ $pendingPaymentCount }}</p><p class="mt-2 text-xs font-semibold text-primary">Lihat tagihan →</p></a>
        <a href="{{ route('orders.index') }}" class="rounded-2xl border border-blue-100 bg-white p-5 transition hover:-translate-y-0.5 hover:border-secondary hover:shadow-lg"><div class="flex items-center justify-between"><p class="text-sm text-slate-500">Pesanan Berjalan</p><i class="fas fa-truck-fast text-secondary" aria-hidden="true"></i></div><p class="mt-2 text-3xl font-black text-slate-900">{{ $activeOrderCount }}</p><p class="mt-2 text-xs font-semibold text-secondary">Pantau pengiriman →</p></a>
        <div class="rounded-2xl bg-primary-dark p-5 text-white"><div class="flex items-center justify-between"><p class="text-sm text-blue-100">Belanja Selesai</p><i class="fas fa-circle-check text-accent-yellow" aria-hidden="true"></i></div><p class="mt-2 text-3xl font-black">{{ $completedOrderCount }}</p><p class="mt-2 text-xs text-blue-100">Rp {{ number_format($completedSpend, 0, ',', '.') }}</p></div>
    </div>

    <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary hover:shadow-md"><span class="grid h-10 w-10 place-items-center rounded-xl bg-red-50 text-red-500"><i class="fas fa-heart"></i></span><span><span class="block font-extrabold text-slate-900">Wishlist</span><span class="text-xs text-slate-500">{{ $wishlistCount }} produk</span></span></a>
        <a href="{{ route('account.addresses.index') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary hover:shadow-md"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-location-dot"></i></span><span><span class="block font-extrabold text-slate-900">Alamat</span><span class="text-xs text-slate-500">{{ $addressCount }} tersimpan</span></span></a>
        <a href="{{ route('account.identity.edit') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary hover:shadow-md"><span class="grid h-10 w-10 place-items-center rounded-xl {{ $identityVerified ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}"><i class="fas fa-id-card"></i></span><span><span class="block font-extrabold text-slate-900">Identitas</span><span class="text-xs text-slate-500">{{ $identityVerified ? 'Terverifikasi' : 'Perlu tindakan' }}</span></span></a>
        <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary hover:shadow-md"><span class="grid h-10 w-10 place-items-center rounded-xl bg-yellow-50 text-yellow-600"><i class="fas fa-bell"></i></span><span><span class="block font-extrabold text-slate-900">Notifikasi</span><span class="text-xs text-slate-500">{{ $unreadNotificationCount }} baru</span></span></a>
        <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary hover:shadow-md"><span class="grid h-10 w-10 place-items-center rounded-xl bg-green-50 text-secondary"><i class="fas fa-user-gear"></i></span><span><span class="block font-extrabold text-slate-900">Akun</span><span class="text-xs text-slate-500">Profil & keamanan</span></span></a>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 p-5"><div><h2 class="font-black text-slate-900">Pesanan Terbaru</h2><p class="text-xs text-slate-500">Status transaksi Anda terkini</p></div><a href="{{ route('orders.index') }}" class="text-sm font-bold text-primary hover:text-secondary">Semua pesanan</a></div>
            <div class="divide-y divide-slate-100">
                @forelse($recentOrders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="flex flex-col gap-3 p-5 transition hover:bg-blue-50/60 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-mono text-sm font-bold text-primary">{{ $order->invoice_number }}</p><p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items->sum('quantity') }} item</p></div><div class="sm:text-right"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-primary">{{ $order->statusLabel() }}</span><p class="mt-2 font-black text-slate-900">Rp {{ number_format($order->total, 0, ',', '.') }}</p></div></a>
                @empty
                    <div class="p-8 text-center"><i class="fas fa-bag-shopping text-3xl text-slate-300" aria-hidden="true"></i><p class="mt-3 text-sm text-slate-500">Belum ada pesanan. Mulai dari katalog toko.</p></div>
                @endforelse
            </div>
        </section>

        <aside class="h-fit rounded-2xl border border-green-100 bg-green-50 p-5">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-secondary text-white"><i class="fas fa-truck" aria-hidden="true"></i></div>
            <h2 class="mt-4 font-black text-slate-900">Kurir Toko</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Semua pesanan dikirim oleh kurir toko. Bukti paket tiba diunggah admin sebelum Anda mengonfirmasi penerimaan.</p>
            <a href="{{ route('orders.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-secondary">Pantau pesanan <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></a>
        </aside>
    </div>

    @if($recommendedProducts->isNotEmpty())
        <section class="mt-10">
            <div class="flex items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-widest text-primary">Rekomendasi</p><h2 class="mt-1 text-2xl font-black text-slate-900">Produk terbaru</h2></div><a href="{{ route('catalog.index') }}" class="text-sm font-bold text-primary hover:text-secondary">Lihat katalog</a></div>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">@foreach($recommendedProducts as $product)<x-product-card :product="$product" :categories="$categories" :wishlisted="in_array($product->id, $wishlistIds, true)" />@endforeach</div>
        </section>
    @endif
</x-layouts.app>
