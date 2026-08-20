<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if(config('services.turnstile.site_key') && request()->routeIs('login', 'register', 'password.request'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-cream/30 text-slate-800 antialiased">
    @php
        $cartCount = auth()->check() && ! auth()->user()->isAdmin()
            ? auth()->user()->cartItems()->sum('quantity')
            : 0;
    @endphp
    <header x-data="{ open: false }" class="border-b-4 border-accent-yellow bg-gradient-to-r from-primary to-secondary text-white shadow-lg shadow-primary/15">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3 py-4">
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-3">
                <img src="{{ asset('img/logo.png') }}?v={{ filemtime(public_path('img/logo.png')) }}" alt="Logo Sipaduhok" data-brand-logo="header" class="h-16 w-16 shrink-0 object-contain drop-shadow-md sm:h-20 sm:w-20">
                <span class="hidden sm:block">
                    <span class="block text-base font-extrabold leading-tight">Toko Sipaduhok</span>
                    <span class="block text-xs text-blue-50">Belanja kebutuhan sekolah</span>
                </span>
            </a>

            <nav class="hidden items-center justify-end gap-1 text-sm lg:flex">
                <a href="{{ route('catalog.index') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Katalog</a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Dashboard</a>
                        <a href="{{ route('admin.products.index') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Produk</a>
                        <a href="{{ route('admin.orders.index') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Pesanan</a>
                        <a href="{{ route('admin.buyers.index') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Pembeli</a>
                        <a href="{{ route('admin.courier.edit') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Kurir</a>
                        <a href="{{ route('admin.settings.payment.edit') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Pembayaran</a>
                    @else
                        <a href="{{ route('buyer.dashboard') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Dashboard</a>
                        <a href="{{ route('orders.index') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Pesanan Saya</a>
                        <a href="{{ route('wishlist.index') }}" class="relative rounded-full px-3 py-2 hover:bg-white/10" aria-label="Wishlist"><i class="fas fa-heart" aria-hidden="true"></i>@if($wishlistCount > 0)<span class="ml-1 inline-grid min-w-5 place-items-center rounded-full bg-accent-yellow px-1.5 py-0.5 text-[10px] font-black text-slate-950">{{ $wishlistCount }}</span>@endif</a>
                    @endif
                    <span class="ml-2 flex items-center gap-2 border-l border-white/20 pl-3">
                        @unless(auth()->user()->isAdmin())
                            <a href="{{ route('cart.index') }}" data-header-cart class="relative grid h-10 w-10 place-items-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/20" aria-label="Buka keranjang, {{ $cartCount }} produk">
                                <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                                @if($cartCount > 0)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-accent-yellow px-1 text-[10px] font-black text-slate-950 ring-2 ring-primary">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif
                            </a>
                        @endunless
                        @include('components.notification-dropdown')
                        @include('components.account-dropdown')
                    </span>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2 hover:bg-white/10">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-white px-4 py-2 font-bold text-primary hover:bg-accent-yellow hover:text-slate-900">Daftar</a>
                @endauth
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-2 lg:hidden" data-mobile-header-actions>
                @auth
                    @unless(auth()->user()->isAdmin())
                        <a href="{{ route('cart.index') }}" data-header-cart class="relative grid h-10 w-10 place-items-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/20" aria-label="Buka keranjang, {{ $cartCount }} produk">
                            <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                            @if($cartCount > 0)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-accent-yellow px-1 text-[10px] font-black text-slate-950 ring-2 ring-primary">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif
                        </a>
                    @endunless
                    @include('components.notification-dropdown')
                    @include('components.account-dropdown')
                @endauth
                <button type="button" @click="open = ! open" class="grid h-11 w-11 place-items-center rounded-xl border border-white/30 text-white" aria-label="Buka menu utama" :aria-expanded="open">
                    <svg x-show="! open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg x-cloak x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </div>
            </div>

            <nav x-cloak x-show="open" x-transition class="grid gap-1 border-t border-white/20 py-3 text-sm lg:hidden">
                <a href="{{ route('catalog.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Katalog</a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Dashboard Admin</a>
                        <a href="{{ route('admin.products.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Kelola Produk</a>
                        <a href="{{ route('admin.products.archived') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Arsip Produk</a>
                        <a href="{{ route('admin.orders.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pesanan Masuk</a>
                        <a href="{{ route('admin.buyers.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-white/10"><span>Kelola Pembeli</span>@if($pendingIdentityCount > 0)<span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white">{{ $pendingIdentityCount }}</span>@endif</a>
                        <a href="{{ route('admin.courier.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Kurir Toko</a>
                        <a href="{{ route('admin.settings.payment.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pembayaran Midtrans</a>
                        <a href="{{ route('admin.settings.store.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Identitas Toko</a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-white/10"><span>Notifikasi</span>@if($unreadNotificationCount > 0)<span class="rounded-full bg-accent-yellow px-2 py-0.5 text-[10px] font-black text-slate-950">{{ $unreadNotificationCount }}</span>@endif</a>
                        <a href="{{ route('account.profile.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Profil Saya</a>
                        <a href="{{ route('account.security.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pengaturan Akun</a>
                        <form method="POST" action="{{ route('admin.logout') }}" data-confirm="Anda akan keluar dari panel Toko Sipaduhok." data-confirm-title="Keluar dari akun?" data-confirm-button="Ya, keluar">@csrf<button class="w-full rounded-lg px-3 py-2.5 text-left font-bold text-accent-yellow hover:bg-white/10">Keluar</button></form>
                    @else
                        <a href="{{ route('buyer.dashboard') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Dashboard</a>
                        <a href="{{ route('cart.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Keranjang @if(($cartCount ?? 0) > 0)({{ $cartCount }})@endif</a>
                        <a href="{{ route('wishlist.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-white/10"><span>Wishlist</span>@if($wishlistCount > 0)<span class="rounded-full bg-accent-yellow px-2 py-0.5 text-[10px] font-black text-slate-950">{{ $wishlistCount }}</span>@endif</a>
                        <a href="{{ route('orders.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pesanan Saya</a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-white/10"><span>Notifikasi</span>@if($unreadNotificationCount > 0)<span class="rounded-full bg-accent-yellow px-2 py-0.5 text-[10px] font-black text-slate-950">{{ $unreadNotificationCount }}</span>@endif</a>
                        <a href="{{ route('account.profile.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Profil Saya</a>
                        <a href="{{ route('account.identity.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Verifikasi KTP</a>
                        <a href="{{ route('account.addresses.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Alamat Tersimpan</a>
                        <a href="{{ route('account.security.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pengaturan Akun</a>
                        <form method="POST" action="{{ route('logout') }}" data-confirm="Anda akan keluar dari akun pembeli." data-confirm-title="Keluar dari akun?" data-confirm-button="Ya, keluar">@csrf<button class="w-full rounded-lg px-3 py-2.5 text-left font-bold text-accent-yellow hover:bg-white/10">Keluar</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg px-3 py-2.5 font-bold text-accent-yellow hover:bg-white/10">Daftar Pembeli</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto min-h-[70vh] max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))<div data-flash-success="{{ session('success') }}" class="hidden"></div>@endif
        @if($errors->any())
            <div data-flash-error="{{ $errors->first() }}" class="hidden"></div>
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @php
            $dashboardUrl = auth()->check() && auth()->user()->isAdmin()
                ? route('admin.dashboard')
                : (auth()->check() ? route('buyer.dashboard') : route('catalog.index'));
            $backNavigation = match (true) {
                request()->routeIs('catalog.index', 'buyer.dashboard', 'admin.dashboard') => null,
                request()->routeIs('catalog.show', 'pages.*') => [route('catalog.index'), 'Kembali ke Katalog'],
                request()->routeIs('login', 'register') => [route('catalog.index'), 'Kembali ke Katalog'],
                request()->routeIs('password.request') => [route('login'), 'Kembali ke Masuk'],
                request()->routeIs('register.otp.*') => [route('register'), 'Kembali ke Pendaftaran'],
                request()->routeIs('recovery.*', 'password.update') => [route('password.request'), 'Kembali ke Pemulihan Akun'],
                request()->routeIs('cart.index', 'wishlist.index') => [route('catalog.index'), 'Kembali ke Katalog'],
                request()->routeIs('checkout.*') => [route('cart.index'), 'Kembali ke Keranjang'],
                request()->routeIs('orders.index') => [route('buyer.dashboard'), 'Kembali ke Dashboard'],
                request()->routeIs('orders.payment') => [route('orders.show', request()->route('order')), 'Kembali ke Detail Pesanan'],
                request()->routeIs('orders.*', 'reviews.*') => [route('orders.index'), 'Kembali ke Pesanan'],
                request()->routeIs('notifications.*', 'account.*') => [$dashboardUrl, 'Kembali ke Dashboard'],
                request()->routeIs('admin.products.index') => [route('admin.dashboard'), 'Kembali ke Dashboard'],
                request()->routeIs('admin.products.*') => [route('admin.products.index'), 'Kembali ke Produk'],
                request()->routeIs('admin.orders.index') => [route('admin.dashboard'), 'Kembali ke Dashboard'],
                request()->routeIs('admin.orders.*') => [route('admin.orders.index'), 'Kembali ke Pesanan'],
                request()->routeIs('admin.buyers.index') => [route('admin.dashboard'), 'Kembali ke Dashboard'],
                request()->routeIs('admin.buyers.*') => [route('admin.buyers.index'), 'Kembali ke Kelola Pembeli'],
                request()->routeIs('admin.reports.*', 'admin.courier.*', 'admin.settings.*') => [route('admin.dashboard'), 'Kembali ke Dashboard'],
                default => null,
            };
        @endphp
        @if($backNavigation)
            <div class="mb-5"><x-back-link :href="$backNavigation[0]" :label="$backNavigation[1]" /></div>
        @endif
        @auth
            @if(auth()->user()->isAdmin() && request()->routeIs('admin.*'))
                <div class="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                    @include('admin._navigation')
                    <div class="min-w-0">{{ $slot }}</div>
                </div>
            @else
                {{ $slot }}
            @endif
        @else
            {{ $slot }}
        @endauth
    </main>

    <footer class="relative overflow-hidden bg-primary-dark text-blue-100">
        <div class="h-1 bg-gradient-to-r from-primary via-secondary to-accent-yellow"></div>
        <div class="pointer-events-none absolute -right-20 top-8 h-56 w-56 rounded-full bg-secondary/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.2fr]">
                <div>
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-3 text-white">
                        <img src="{{ asset('img/logo.png') }}?v={{ filemtime(public_path('img/logo.png')) }}" alt="Logo Sipaduhok" data-brand-logo="footer" class="h-20 w-20 shrink-0 object-contain drop-shadow-lg">
                        <span>
                            <span class="block font-extrabold leading-tight">{{ $storeSettings['legal_name'] }}</span>
                            <span class="block text-xs text-blue-200">Belanja kebutuhan sekolah</span>
                        </span>
                    </a>
                    <p class="mt-5 max-w-sm text-sm leading-6 text-blue-100">
                        {{ $storeSettings['description'] }}
                    </p>
                    <div class="mt-5 grid max-w-sm gap-2 text-xs font-semibold text-blue-100 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <span class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3 py-2"><i class="fas fa-shield-halved text-emerald-300" aria-hidden="true"></i>Pembayaran aman</span>
                        <span class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3 py-2"><i class="fas fa-file-invoice text-accent-yellow" aria-hidden="true"></i>Invoice elektronik</span>
                        <span class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3 py-2"><i class="fas fa-headset text-blue-200" aria-hidden="true"></i>Dukungan pelanggan</span>
                        <span class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3 py-2"><i class="fas fa-truck text-blue-200" aria-hidden="true"></i>Pengiriman terlacak</span>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Menu</h2>
                    <nav class="mt-4 flex flex-col gap-3 text-sm">
                        <a href="{{ route('catalog.index') }}" class="transition hover:text-accent-yellow">Katalog Produk</a>
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="transition hover:text-accent-yellow">Dashboard Admin</a>
                                <a href="{{ route('admin.orders.index') }}" class="transition hover:text-accent-yellow">Pesanan Masuk</a>
                            @else
                                <a href="{{ route('buyer.dashboard') }}" class="transition hover:text-accent-yellow">Dashboard Pembeli</a>
                                <a href="{{ route('cart.index') }}" class="transition hover:text-accent-yellow">Keranjang</a>
                                <a href="{{ route('orders.index') }}" class="transition hover:text-accent-yellow">Riwayat Pesanan</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="transition hover:text-accent-yellow">Masuk</a>
                            <a href="{{ route('register') }}" class="transition hover:text-accent-yellow">Daftar Pembeli</a>
                        @endauth
                    </nav>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Informasi</h2>
                    <nav class="mt-4 flex flex-col gap-3 text-sm text-blue-100">
                        <a href="{{ route('pages.about') }}" class="transition hover:text-accent-yellow">Tentang Toko</a>
                        <a href="{{ route('pages.payment') }}" class="transition hover:text-accent-yellow">Cara Pembayaran</a>
                        <a href="{{ route('pages.shipping') }}" class="transition hover:text-accent-yellow">Kebijakan Pengiriman</a>
                        <a href="{{ route('pages.returns') }}" class="transition hover:text-accent-yellow">Pembatalan & Pengembalian</a>
                        <a href="{{ route('pages.help') }}" class="transition hover:text-accent-yellow">Pusat Bantuan</a>
                    </nav>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Kontak Resmi</h2>
                    <p class="mt-4 break-all text-sm font-semibold text-blue-100"><i class="fas fa-envelope mr-2 text-accent-yellow"></i>{{ $storeSettings['support_email'] }}</p>
                    @if($storeSettings['phone'])<p class="mt-3 text-sm text-blue-100"><i class="fas fa-phone mr-2 text-accent-yellow"></i>{{ $storeSettings['phone'] }}</p>@endif
                    @if($storeSettings['address'] && ! str_contains(strtolower($storeSettings['address']), 'belum diatur'))<p class="mt-3 text-xs leading-5 text-blue-100"><i class="fas fa-location-dot mr-2 text-accent-yellow"></i>{{ $storeSettings['address'] }}</p>@endif
                    <p class="mt-3 text-xs leading-5 text-blue-200">{{ $storeSettings['operating_hours'] }}</p>
                    <p class="mt-5 rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-xs leading-5 text-blue-100"><i class="fas fa-circle-check mr-2 text-emerald-300" aria-hidden="true"></i>Pastikan komunikasi hanya melalui kontak resmi yang tercantum di situs ini.</p>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-white/15 pt-6 text-xs text-blue-200 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ $storeSettings['legal_name'] }}. Seluruh hak dilindungi.</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2"><a href="{{ route('pages.privacy') }}" class="hover:text-accent-yellow">Kebijakan Privasi</a><a href="{{ route('pages.terms') }}" class="hover:text-accent-yellow">Syarat & Ketentuan</a><span class="font-medium text-blue-100">toko.sipaduhok.id</span></div>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
