<x-layouts.app title="Pengaturan Pembayaran Midtrans">
    <div class="max-w-4xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan pembayaran</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">Midtrans Payment Gateway</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Kelola kredensial transaksi langsung Toko Sipaduhok. Server Key dan Client Key yang disimpan melalui panel dienkripsi menggunakan APP_KEY.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-2 text-xs font-extrabold {{ $status['ready'] ? 'bg-emerald-100 text-emerald-700' : ($status['enabled'] ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600') }}">
                <span class="h-2 w-2 rounded-full {{ $status['ready'] ? 'bg-emerald-500' : ($status['enabled'] ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                {{ $status['ready'] ? 'Siap menerima pembayaran' : ($status['enabled'] ? 'Konfigurasi belum lengkap' : 'Midtrans nonaktif') }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Environment</p><p class="mt-2 font-black text-slate-900">{{ $status['environment'] === 'production' ? 'Production (Live)' : 'Sandbox (Testing)' }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Server Key</p><p class="mt-2 font-black {{ $status['server_key_configured'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $status['server_key_configured'] ? 'Tersedia' : 'Belum diisi' }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Client Key</p><p class="mt-2 font-black {{ $status['client_key_configured'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $status['client_key_configured'] ? 'Tersedia' : 'Belum diisi' }}</p></div>
        </div>

        <section x-data="{ copied: false, async copyCallback() { await navigator.clipboard.writeText(@js($callbackUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000) } }" class="mt-6 rounded-2xl border border-primary/20 bg-blue-50 p-5">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary text-white"><i class="fas fa-link" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-extrabold text-slate-900">Payment Notification URL</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Pasang URL ini pada dashboard Midtrans agar status pembayaran masuk melalui callback server-to-server.</p>
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
            x-data="{ showServer: false, showClient: false, showPassword: false }"
            data-confirm="Perubahan ini langsung digunakan untuk transaksi dan verifikasi callback Midtrans berikutnya."
            data-confirm-title="Simpan konfigurasi Midtrans?"
            data-confirm-button="Ya, simpan"
        >
            @csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-semibold sm:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <span class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $status['enabled'])) class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                        <span><span class="block font-extrabold text-slate-900">Aktifkan Midtrans untuk checkout</span><span class="mt-1 block text-xs font-normal leading-5 text-slate-500">Jika dinonaktifkan, mode placeholder hanya tersedia untuk development atau konfirmasi internal yang sudah ada.</span></span>
                    </span>
                </label>

                <label class="text-sm font-semibold">Environment
                    <select name="environment" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3">
                        <option value="sandbox" @selected(old('environment', $status['environment']) === 'sandbox')>Sandbox (Testing)</option>
                        <option value="production" @selected(old('environment', $status['environment']) === 'production')>Production (Live)</option>
                    </select>
                </label>

                <label class="text-sm font-semibold">Merchant ID
                    <input name="merchant_id" value="{{ old('merchant_id', $status['merchant_id']) }}" maxlength="100" autocomplete="off" placeholder="Contoh: G123456789" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                    <span class="mt-1 block text-xs font-normal text-slate-400">Identitas merchant dari dashboard Midtrans.</span>
                </label>

                <label class="text-sm font-semibold">Server Key
                    <span class="relative mt-1 block">
                        <input name="server_key" :type="showServer ? 'text' : 'password'" maxlength="255" autocomplete="new-password" placeholder="{{ $status['server_key_configured'] ? 'Tersimpan — kosongkan jika tidak diganti' : 'SB-Mid-server-...' }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 font-mono text-sm">
                        <button type="button" @click="showServer = ! showServer" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showServer ? 'Sembunyikan Server Key' : 'Tampilkan Server Key'"><i class="fas" :class="showServer ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                    <span class="mt-1 block text-xs font-normal text-red-500">Rahasia. Nilai tersimpan tidak pernah ditampilkan kembali.</span>
                </label>

                <label class="text-sm font-semibold">Client Key
                    <span class="relative mt-1 block">
                        <input name="client_key" :type="showClient ? 'text' : 'password'" maxlength="255" autocomplete="new-password" placeholder="{{ $status['client_key_configured'] ? 'Tersimpan — kosongkan jika tidak diganti' : 'SB-Mid-client-...' }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 font-mono text-sm">
                        <button type="button" @click="showClient = ! showClient" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showClient ? 'Sembunyikan Client Key' : 'Tampilkan Client Key'"><i class="fas" :class="showClient ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                    <span class="mt-1 block text-xs font-normal text-slate-400">Digunakan Snap.js pada halaman pembayaran.</span>
                </label>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 sm:col-span-2">
                    <strong>Catatan rotasi key:</strong> ubah key di panel ini setelah key yang sama aktif di dashboard Midtrans. Mengganti Server Key dapat membuat callback transaksi lama gagal diverifikasi.
                </div>

                <label x-data class="text-sm font-semibold sm:col-span-2">Konfirmasi kata sandi admin
                    <span class="relative mt-1 block sm:max-w-md">
                        <input name="current_password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14">
                        <button type="button" @click="showPassword = ! showPassword" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-primary" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"><i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i></button>
                    </span>
                </label>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-slate-400">IRIS, escrow, payout seller, dan API ongkir eksternal tidak digunakan pada sistem toko satu penjual ini.</p>
                <button class="shrink-0 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-shield-halved mr-2" aria-hidden="true"></i>Simpan Midtrans</button>
            </div>
        </form>
    </div>
</x-layouts.app>
