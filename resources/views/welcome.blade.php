<x-layouts.app title="Koperasi Sipaduhok">
    <section class="rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-14 text-center text-white shadow-xl shadow-primary/15 sm:px-10">
        <img src="{{ asset('img/logo.png') }}" alt="Logo Sipaduhok" class="mx-auto h-24 w-24 object-contain drop-shadow-lg">
        <p class="mt-6 text-sm font-bold uppercase tracking-widest text-accent-yellow">Koperasi Sekolah</p>
        <h1 class="mt-3 text-4xl font-black">Koperasi Sipaduhok</h1>
        <p class="mx-auto mt-4 max-w-xl text-blue-50">Belanja buku, alat tulis, dan atribut sekolah dengan pengantaran langsung oleh Kurir Koperasi.</p>
        <a href="{{ route('catalog.index') }}" class="mt-8 inline-flex rounded-full bg-white px-6 py-3 font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900">Buka Katalog</a>
    </section>
</x-layouts.app>
