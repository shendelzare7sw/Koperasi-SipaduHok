<x-layouts.app title="Keamanan Akun - Koperasi Sipaduhok">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan akun</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Keamanan Akun</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui kata sandi untuk menjaga akses akun tetap aman.</p>
        </div>

        @include('account._navigation')

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
            <form method="POST" action="{{ route('account.security.update') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" data-confirm="Kata sandi lama tidak dapat dipakai kembali setelah perubahan disimpan." data-confirm-title="Ubah kata sandi akun?" data-confirm-icon="warning" data-confirm-button="Ya, ubah kata sandi">
                @csrf
                @method('PUT')
                <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="font-extrabold text-slate-900">Ubah kata sandi</h2>
                        <p class="text-xs text-slate-500">Gunakan minimal 8 karakter, huruf, dan angka.</p>
                    </div>
                </div>
                <div class="mt-6 space-y-5">
                    @foreach([
                        ['current_password', 'Kata sandi saat ini', 'current-password'],
                        ['password', 'Kata sandi baru', 'new-password'],
                        ['password_confirmation', 'Konfirmasi kata sandi baru', 'new-password'],
                    ] as [$name, $label, $autocomplete])
                        <label x-data="{ show: false }" class="block text-sm font-semibold">{{ $label }}
                            <span class="relative mt-1 block">
                                <input name="{{ $name }}" :type="show ? 'text' : 'password'" required autocomplete="{{ $autocomplete }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary transition hover:text-secondary" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
                                </button>
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-7 flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>

            <aside class="h-fit rounded-2xl border border-secondary/20 bg-green-50 p-5">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-secondary text-white"><i class="fas fa-shield" aria-hidden="true"></i></span>
                <h2 class="mt-4 font-extrabold text-slate-900">Tips keamanan</h2>
                <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                    <li class="flex gap-2"><i class="fas fa-check mt-1.5 text-secondary" aria-hidden="true"></i><span>Jangan gunakan kata sandi akun sekolah lainnya.</span></li>
                    <li class="flex gap-2"><i class="fas fa-check mt-1.5 text-secondary" aria-hidden="true"></i><span>Hindari membagikan kata sandi kepada siapa pun.</span></li>
                    <li class="flex gap-2"><i class="fas fa-check mt-1.5 text-secondary" aria-hidden="true"></i><span>Keluar dari akun saat memakai perangkat bersama.</span></li>
                </ul>
            </aside>
        </div>
    </div>
</x-layouts.app>
