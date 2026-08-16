<x-layouts.app title="Atur Ulang Kata Sandi - Koperasi Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-xl text-primary"><i class="fas fa-key"></i></span>
        <h1 class="mt-5 text-2xl font-black text-slate-900">Atur Ulang Kata Sandi</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan minimal delapan karakter yang memuat huruf dan angka.</p>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4" data-confirm="Kata sandi akun akan diganti dengan kata sandi baru." data-confirm-title="Simpan kata sandi baru?" data-confirm-button="Ya, simpan">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block text-sm font-semibold">Email<input name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            @foreach([['password', 'Kata sandi baru'], ['password_confirmation', 'Konfirmasi kata sandi']] as [$name, $label])
                <label x-data="{ show: false }" class="block text-sm font-semibold">{{ $label }}
                    <span class="relative mt-1 block"><input name="{{ $name }}" :type="show ? 'text' : 'password'" required autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14"><button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary hover:text-secondary" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></span>
                </label>
            @endforeach
            <button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white hover:bg-secondary">Simpan Kata Sandi Baru</button>
        </form>
    </div>
</x-layouts.app>
