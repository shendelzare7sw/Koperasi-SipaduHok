<x-layouts.app title="Daftar Pembeli - Koperasi Sipaduhok">
    <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-black text-slate-900">Daftar Pembeli</h1>
        <p class="mt-1 text-sm text-slate-500">Buat satu akun pembeli. Kode OTP akan dikirim ke email untuk memverifikasi pendaftaran.</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2" data-confirm="Pastikan nama, nomor HP, dan email sudah benar. Kami akan mengirim kode OTP ke email tersebut." data-confirm-title="Kirim kode verifikasi?" data-confirm-button="Ya, kirim OTP">
            @csrf
            <label class="block text-sm font-semibold sm:col-span-2">Nama pemilik akun
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold sm:col-span-2">Nomor HP
                <input name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold sm:col-span-2">Email
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label x-data="{ show: false }" class="block text-sm font-semibold">Kata sandi
                <span class="relative mt-1 block">
                    <input name="password" :type="show ? 'text' : 'password'" required class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-20">
                    <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary transition hover:text-secondary" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                </span>
            </label>
            <label x-data="{ show: false }" class="block text-sm font-semibold">Konfirmasi kata sandi
                <span class="relative mt-1 block">
                    <input name="password_confirmation" :type="show ? 'text' : 'password'" required class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-20">
                    <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary transition hover:text-secondary" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                </span>
            </label>
            <div class="sm:col-span-2"><x-turnstile action="register" /></div>
            <button class="rounded-xl bg-primary px-4 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary sm:col-span-2"><i class="fas fa-shield-alt mr-2"></i>Kirim Kode OTP</button>
        </form>
    </div>
</x-layouts.app>
