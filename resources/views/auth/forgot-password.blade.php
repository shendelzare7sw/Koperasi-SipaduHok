<x-layouts.app title="Pulihkan Akun - Toko Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-xl text-primary"><i class="fas fa-user-shield"></i></span>
        <h1 class="mt-5 text-2xl font-black text-slate-900">Pulihkan Akun</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan email atau nomor HP yang terdaftar. Kode OTP akan dikirim ke email akun untuk mengatur ulang kata sandi.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" data-confirm="Kode pemulihan akan dikirim ke email akun yang ditemukan." data-confirm-title="Kirim kode pemulihan?" data-confirm-button="Ya, kirim OTP">
            @csrf
            <label class="block text-sm font-semibold">Email atau nomor HP<input name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username" placeholder="nama@email.com atau 0812..." class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></label>
            <x-turnstile action="recovery" />
            <button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-paper-plane mr-2"></i>Kirim Kode OTP</button>
        </form>
    </div>
</x-layouts.app>
