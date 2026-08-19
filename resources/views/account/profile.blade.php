<x-layouts.app title="Profil Saya - Toko Sipaduhok">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan akun</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Profil Saya</h1>
            <p class="mt-2 text-sm text-slate-500">Kelola identitas dan kontak yang digunakan pada akun toko.</p>
        </div>

        @include('account._navigation')

        <div class="mt-6 grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl bg-gradient-to-br from-primary to-secondary p-6 text-white shadow-lg shadow-primary/15">
                <div class="grid h-20 w-20 place-items-center rounded-2xl bg-white/15 text-3xl font-black ring-1 ring-white/25">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <h2 class="mt-5 break-words text-xl font-extrabold">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-blue-100">{{ $user->role->label() }}</p>
                <dl class="mt-6 space-y-4 border-t border-white/20 pt-5 text-sm">
                    <div>
                        <dt class="text-blue-100">Anggota sejak</dt>
                        <dd class="mt-1 font-bold">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-blue-100">ID akun</dt>
                        <dd class="mt-1 font-bold">KSP-{{ str_pad((string) $user->id, 6, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                </dl>
            </aside>

            <form method="POST" action="{{ route('account.profile.update') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" data-confirm="Nama, email, dan nomor HP akun akan diperbarui." data-confirm-title="Simpan perubahan profil?" data-confirm-button="Ya, simpan">
                @csrf
                @method('PATCH')
                <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-address-card" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="font-extrabold text-slate-900">Informasi pribadi</h2>
                        <p class="text-xs text-slate-500">Role akun tidak dapat diubah dari halaman ini.</p>
                    </div>
                </div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-semibold sm:col-span-2">Nama lengkap
                        <input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </label>
                    <label class="block text-sm font-semibold">Email
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </label>
                    <label class="block text-sm font-semibold">Nomor HP
                        <input name="phone" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" inputmode="tel" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">Role akun
                        <span class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600">
                            <span>{{ $user->role->label() }}</span>
                            <i class="fas fa-lock text-slate-400" aria-hidden="true"></i>
                        </span>
                    </label>
                </div>
                <div class="mt-7 flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary">
                        <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                        Simpan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
