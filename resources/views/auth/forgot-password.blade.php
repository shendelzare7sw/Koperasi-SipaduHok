<x-layouts.app title="Lupa Kata Sandi - Koperasi Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-xl text-primary"><i class="fas fa-envelope-open-text"></i></span>
        <h1 class="mt-5 text-2xl font-black text-slate-900">Lupa Kata Sandi</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan email akun. Sistem akan mengirim tautan aman untuk membuat kata sandi baru.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" data-confirm="Tautan pemulihan akan dikirim ke email yang dimasukkan." data-confirm-title="Kirim tautan pemulihan?" data-confirm-button="Ya, kirim">
            @csrf
            <label class="block text-sm font-semibold">Email akun<input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></label>
            <button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-paper-plane mr-2"></i>Kirim Tautan Reset</button>
        </form>
        <a href="{{ route('login') }}" class="mt-5 block text-center text-sm font-bold text-primary hover:text-secondary"><i class="fas fa-arrow-left mr-2"></i>Kembali ke halaman masuk</a>
    </div>
</x-layouts.app>
