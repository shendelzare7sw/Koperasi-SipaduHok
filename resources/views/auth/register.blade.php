<x-layouts.app title="Daftar Pembeli - Koperasi Sipaduhok">
    <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-black text-slate-900">Daftar Pembeli</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih apakah akun digunakan siswa atau orang tua/wali.</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <label class="block text-sm font-semibold sm:col-span-2">Nama pemilik akun
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold">Tipe pembeli
                <select name="buyer_type" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                    @foreach($buyerTypes as $type)<option value="{{ $type->value }}" @selected(old('buyer_type') === $type->value)>{{ $type->label() }}</option>@endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold">Nomor HP
                <input name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold sm:col-span-2">Email
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold">Kata sandi
                <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block text-sm font-semibold">Konfirmasi kata sandi
                <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <button class="rounded-xl bg-orange-500 px-4 py-3 font-bold text-slate-950 sm:col-span-2">Buat Akun Pembeli</button>
        </form>
    </div>
</x-layouts.app>
