<x-layouts.app title="Toko Sipaduhok">
    <section class="rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-14 text-center text-white shadow-xl shadow-primary/15 sm:px-10">
        <img src="{{ asset('img/logo.png') }}?v={{ filemtime(public_path('img/logo.png')) }}" alt="Logo Sipaduhok" class="mx-auto h-32 w-32 object-contain drop-shadow-lg">
        <p class="mt-6 text-sm font-bold uppercase tracking-widest text-accent-yellow">Toko Kebutuhan Sekolah</p>
        <h1 class="mt-3 text-4xl font-black">Toko Sipaduhok</h1>
        <p class="mx-auto mt-4 max-w-xl text-blue-50">Belanja buku, alat tulis, dan kebutuhan sekolah dengan pembayaran digital serta pengantaran langsung oleh Kurir Toko.</p>
        <p class="mx-auto mt-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white sm:text-sm"><i class="fas fa-location-dot text-accent-yellow" aria-hidden="true"></i>Melayani pengiriman khusus wilayah Tangerang dan sekitarnya</p>
        <a href="{{ route('catalog.index') }}" class="mt-8 inline-flex rounded-full bg-white px-6 py-3 font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900">Buka Katalog</a>
    </section>
</x-layouts.app>
