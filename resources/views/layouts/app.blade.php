<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="border-b-4 border-orange-500 bg-slate-900 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-orange-500 font-black text-slate-950">KS</span>
                <span>
                    <span class="block text-base font-extrabold leading-tight">Koperasi Sipaduhok</span>
                    <span class="block text-xs text-slate-300">Belanja kebutuhan sekolah</span>
                </span>
            </a>

            <nav class="flex flex-wrap items-center justify-end gap-2 text-sm">
                <a href="{{ route('catalog.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Katalog</a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Dashboard</a>
                        <a href="{{ route('admin.products.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Produk</a>
                        <a href="{{ route('admin.orders.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Pesanan</a>
                        <a href="{{ route('admin.courier.edit') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Kurir</a>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf
                            <button class="rounded-lg bg-orange-500 px-3 py-2 font-bold text-slate-950">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('orders.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Pesanan Saya</a>
                        <a href="{{ route('cart.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Keranjang</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="rounded-lg bg-orange-500 px-3 py-2 font-bold text-slate-950">Keluar</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 hover:bg-slate-800">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-orange-500 px-3 py-2 font-bold text-slate-950">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto min-h-[70vh] max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="relative overflow-hidden bg-slate-950 text-slate-300">
        <div class="h-1 bg-gradient-to-r from-orange-600 via-orange-400 to-amber-300"></div>
        <div class="pointer-events-none absolute -right-20 top-8 h-56 w-56 rounded-full bg-orange-500/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.2fr]">
                <div>
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-3 text-white">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-orange-500 font-black text-slate-950 shadow-lg shadow-orange-950/40">KS</span>
                        <span>
                            <span class="block font-extrabold leading-tight">Koperasi Sipaduhok</span>
                            <span class="block text-xs text-slate-400">Belanja kebutuhan sekolah</span>
                        </span>
                    </a>
                    <p class="mt-5 max-w-sm text-sm leading-6 text-slate-400">
                        Toko koperasi sekolah untuk buku, alat tulis, dan atribut sekolah dengan pemesanan yang mudah dipantau.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Bagian dari ekosistem Sipaduhok
                    </span>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Menu</h2>
                    <nav class="mt-4 flex flex-col gap-3 text-sm">
                        <a href="{{ route('catalog.index') }}" class="transition hover:text-orange-400">Katalog Produk</a>
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="transition hover:text-orange-400">Dashboard Admin</a>
                                <a href="{{ route('admin.orders.index') }}" class="transition hover:text-orange-400">Pesanan Masuk</a>
                            @else
                                <a href="{{ route('cart.index') }}" class="transition hover:text-orange-400">Keranjang</a>
                                <a href="{{ route('orders.index') }}" class="transition hover:text-orange-400">Riwayat Pesanan</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="transition hover:text-orange-400">Masuk</a>
                            <a href="{{ route('register') }}" class="transition hover:text-orange-400">Daftar Pembeli</a>
                        @endauth
                    </nav>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Layanan</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-400">
                        <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Buku & alat tulis</li>
                        <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Atribut sekolah</li>
                        <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Kurir Koperasi</li>
                        <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Invoice digital</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Sistem Sekolah</h2>
                    <p class="mt-4 text-sm leading-6 text-slate-400">Akses layanan akademik dan administrasi melalui aplikasi utama Sipaduhok.</p>
                    <a href="https://app.sipaduhok.id" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:border-orange-500 hover:text-orange-400">
                        Buka app.sipaduhok.id
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M14 3h7v7M10 14 21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-slate-800 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} Koperasi Sipaduhok. Seluruh hak dilindungi.</p>
                <p class="font-medium text-slate-400">koperasi.sipaduhok.id</p>
            </div>
        </div>
    </footer>
</body>
</html>
