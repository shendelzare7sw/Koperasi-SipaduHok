<x-layouts.app title="Masuk - Koperasi Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-black text-slate-900">Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">Siswa, orang tua, dan admin menggunakan halaman masuk yang sama.</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-semibold">Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200">
            </label>
            <label class="block text-sm font-semibold">Kata sandi
                <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Ingat saya</label>
            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 font-bold text-white hover:bg-slate-800">Masuk</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-orange-600">Daftar pembeli</a></p>
    </div>
</x-layouts.app>
