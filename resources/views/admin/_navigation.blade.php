<aside class="h-fit min-w-0 rounded-2xl bg-gradient-to-b from-primary to-primary-dark p-3 text-white shadow-lg shadow-primary/15 lg:sticky lg:top-6 lg:p-4">
    <div class="hidden border-b border-white/15 px-2 pb-4 lg:block">
        <p class="text-xs font-extrabold uppercase tracking-widest text-accent-yellow">Admin Seller</p>
        <p class="mt-1 truncate font-bold">{{ auth()->user()->name }}</p>
    </div>
    @php
        $activeClass = 'bg-accent-yellow text-slate-900';
        $idleClass = 'text-blue-100 hover:bg-white/10 hover:text-white';
    @endphp
    <nav class="flex gap-2 overflow-x-auto lg:mt-3 lg:grid lg:gap-1" aria-label="Menu admin koperasi">
        <a href="{{ route('admin.dashboard') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.dashboard') ? $activeClass : $idleClass }}">Ringkasan</a>
        <a href="{{ route('admin.products.index') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.products.*') && ! request()->routeIs('admin.products.import.*', 'admin.products.archived') ? $activeClass : $idleClass }}">Produk</a>
        <a href="{{ route('admin.products.archived') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.products.archived') ? $activeClass : $idleClass }}">Arsip Produk</a>
        <a href="{{ route('admin.products.import.index') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.products.import.*') ? $activeClass : $idleClass }}">Import Excel</a>
        <a href="{{ route('admin.orders.index') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.orders.*') && ! request('status') ? $activeClass : $idleClass }}">Semua Pesanan</a>
        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.orders.index') && request('status') === 'processing' ? $activeClass : $idleClass }}">Perlu Diproses</a>
        <a href="{{ route('admin.orders.index', ['status' => 'ready']) }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.orders.index') && request('status') === 'ready' ? $activeClass : $idleClass }}">Siap Dikirim</a>
        <a href="{{ route('admin.orders.index', ['status' => 'out_for_delivery']) }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.orders.index') && request('status') === 'out_for_delivery' ? $activeClass : $idleClass }}">Dalam Pengantaran</a>
        <a href="{{ route('admin.buyers.index') }}" class="flex items-center justify-between gap-2 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.buyers.*') ? $activeClass : $idleClass }}"><span>Akun Pembeli</span>@if($pendingIdentityCount > 0)<span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white">{{ $pendingIdentityCount }}</span>@endif</a>
        <a href="{{ route('admin.reports.sales') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.reports.*') ? $activeClass : $idleClass }}">Laporan Penjualan</a>
        <a href="{{ route('admin.courier.edit') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.courier.*') ? $activeClass : $idleClass }}">Kurir & Tarif</a>
        <a href="{{ route('admin.settings.payment.edit') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.settings.payment.*') ? $activeClass : $idleClass }}">Pembayaran</a>
        <a href="{{ route('admin.settings.store.edit') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('admin.settings.store.*') ? $activeClass : $idleClass }}">Identitas Koperasi</a>
        <a href="{{ route('catalog.index') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-bold {{ $idleClass }}">Lihat Toko ↗</a>
    </nav>
</aside>
