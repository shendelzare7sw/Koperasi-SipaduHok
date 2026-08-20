<x-layouts.app title="Pengaturan Pembayaran Paywuz">
    <div class="max-w-4xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan pembayaran</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">Paywuz Payment Gateway</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Kelola API key proyek Sandbox dan Production. Seluruh key disimpan terenkripsi menggunakan APP_KEY dan hanya dipakai dari server.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-2 text-xs font-extrabold {{ $status['ready'] ? 'bg-emerald-100 text-emerald-700' : ($status['enabled'] ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600') }}">
                <span class="h-2 w-2 rounded-full {{ $status['ready'] ? 'bg-emerald-500' : ($status['enabled'] ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                {{ $status['ready'] ? 'Siap menerima pembayaran' : ($status['enabled'] ? 'Konfigurasi belum lengkap' : 'Paywuz nonaktif') }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Environment Aktif</p><p class="mt-2 font-black text-slate-900">{{ $status['environment'] === 'production' ? 'Production (Live)' : 'Sandbox (Testing)' }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Sandbox API Key</p><p class="mt-2 font-black {{ $status['sandbox_api_key_configured'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $status['sandbox_api_key_configured'] ? 'Tersedia' : 'Belum diisi' }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Production API Key</p><p class="mt-2 font-black {{ $status['production_api_key_configured'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $status['production_api_key_configured'] ? 'Tersedia' : 'Belum diisi' }}</p></div>
        </div>

        <section x-data="{ copied: false, async copyCallback() { await navigator.clipboard.writeText(@js($callbackUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000) } }" class="mt-6 rounded-2xl border border-primary/20 bg-blue-50 p-5">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary text-white"><i class="fas fa-link" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-extrabold text-slate-900">Webhook URL Paywuz</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Pasang URL ini sebagai Webhook URL pada proyek Sandbox dan Production di dashboard Paywuz.</p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <input readonly value="{{ $callbackUrl }}" class="min-w-0 flex-1 rounded-xl border border-primary/20 bg-white px-4 py-3 font-mono text-xs text-slate-700">
                        <button type="button" @click="copyCallback" class="rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white hover:bg-secondary"><i class="fas mr-2" :class="copied ? 'fa-check' : 'fa-copy'" aria-hidden="true"></i><span x-text="copied ? 'Tersalin' : 'Salin URL'"></span></button>
                    </div>
                </div>
            </div>
        </section>

        <form
            method="POST"
            action="{{ route('admin.settings.payment.update') }}"
            class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
            x-data="{ showSandbox: false, showProduction: false, showPassword: false }"
            data-confirm="Perubahan ini langsung digunakan untuk transaksi baru dan verifikasi webhook Paywuz."
            data-confirm-title="Simpan konfigurasi Paywuz?"
            data-confirm-button="Ya, simpan"
        >
            @csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-semibold sm:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <span class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $status['enabled'])) class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                        <span><span class="block font-extrabold text-slate-900">Aktifkan Paywuz untuk checkout</span><span class="mt-1 block text-xs font-normal leading-5 text-slate-500">Jika dinonaktifkan, transaksi baru tidak akan dikirim ke Paywuz.</span></span>
                    </span>
                </label>

                <label class="text-sm font-semibold sm:col-span-2">Environment aktif
                    <select name="environment" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 sm:max-w-md">
                        <option value="sandbox" @selected(old('environment', $status['environment']) === 'sandbox')>Sandbox (data simulasi)</option>
                        <option value="production" @selected(old('environment', $status['environment']) === 'production')>Production (transaksi nyata)</option>
                    </select>
                </label>

                <label class="text-sm font-semibold">Sandbox API Key
                    <span class="relative mt-1 block">
                        <input name="sandbox_api_key" :type="showSandbox ? 'text' : 'password'" maxlength="255" autocomplete="new-password" placeholder="{{ $status['sandbox_api_key_configured'] ? 'Tersimpan — kosongkan jika tidak diganti' : 'pk_sand_...' }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 font-mono text-sm">
                        <button type="button" @click="showSandbox = ! showSandbox" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showSandbox ? 'Sembunyikan Sandbox API Key' : 'Tampilkan Sandbox API Key'"><i class="fas" :class="showSandbox ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                    <span class="mt-1 block text-xs font-normal text-slate-400">Gunakan key proyek Sandbox berawalan pk_sand_.</span>
                </label>

                <label class="text-sm font-semibold">Production API Key
                    <span class="relative mt-1 block">
                        <input name="production_api_key" :type="showProduction ? 'text' : 'password'" maxlength="255" autocomplete="new-password" placeholder="{{ $status['production_api_key_configured'] ? 'Tersimpan — kosongkan jika tidak diganti' : 'pk_live_...' }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 font-mono text-sm">
                        <button type="button" @click="showProduction = ! showProduction" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showProduction ? 'Sembunyikan Production API Key' : 'Tampilkan Production API Key'"><i class="fas" :class="showProduction ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                    <span class="mt-1 block text-xs font-normal text-slate-400">Gunakan key proyek Production berawalan pk_live_.</span>
                </label>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 sm:col-span-2">
                    <strong>Keamanan key:</strong> API key Paywuz berfungsi sekaligus sebagai Bearer token dan secret HMAC webhook. Jangan menaruh key di JavaScript, Blade, screenshot, atau repository.
                </div>

                <label class="text-sm font-semibold sm:col-span-2">Konfirmasi kata sandi admin
                    <span class="relative mt-1 block sm:max-w-md">
                        <input name="current_password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14">
                        <button type="button" @click="showPassword = ! showPassword" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"><i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                </label>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-slate-400">Pilihan metode checkout mengikuti kanal aktif yang dikembalikan API Paywuz.</p>
                <button class="shrink-0 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-shield-halved mr-2" aria-hidden="true"></i>Simpan Paywuz</button>
            </div>
        </form>
    </div>
</x-layouts.app>
