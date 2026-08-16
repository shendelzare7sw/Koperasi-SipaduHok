<x-layouts.app title="Masuk - Koperasi Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-black text-slate-900">Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">Pembeli dan admin koperasi menggunakan halaman masuk yang sama.</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-semibold">Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </label>
            <label x-data="{ show: false }" class="block text-sm font-semibold">Kata sandi
                <span class="relative mt-1 block">
                    <input name="password" :type="show ? 'text' : 'password'" required class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary transition hover:text-secondary" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
                    </button>
                </span>
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Ingat saya</label>
            <div class="text-right"><a href="{{ route('password.request') }}" class="text-sm font-bold text-primary hover:text-secondary">Lupa kata sandi?</a></div>
            <button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary">Masuk</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-primary hover:text-secondary">Daftar pembeli</a></p>
    </div>
</x-layouts.app>
