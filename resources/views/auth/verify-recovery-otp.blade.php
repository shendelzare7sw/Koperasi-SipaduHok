<x-layouts.app title="Verifikasi Pemulihan Akun - Toko Sipaduhok">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-xl text-primary"><i class="fas fa-shield-halved"></i></span>
        <h1 class="mt-5 text-2xl font-black text-slate-900">Verifikasi Pemulihan</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan enam digit kode OTP yang dikirim ke <strong class="text-slate-700">{{ $maskedEmail }}</strong>. Jangan berikan kode ini kepada siapa pun.</p>

        <form method="POST" action="{{ route('recovery.otp.verify') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-semibold">Kode OTP
                <input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus placeholder="000000" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-center text-2xl font-black tracking-[0.5em] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </label>
            <button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-unlock-keyhole mr-2"></i>Verifikasi Akun</button>
        </form>

        <form method="POST" action="{{ route('recovery.otp.resend') }}" class="mt-3" data-confirm="Kode OTP sebelumnya akan diganti dengan kode baru." data-confirm-title="Kirim ulang kode pemulihan?" data-confirm-button="Ya, kirim ulang" x-data="{ wait: {{ $canResendIn }} }" x-init="if (wait > 0) { const timer = setInterval(() => { wait--; if (wait <= 0) clearInterval(timer) }, 1000) }">
            @csrf
            <button :disabled="wait > 0" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-primary hover:border-primary hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50"><i class="fas fa-rotate mr-2"></i><span x-text="wait > 0 ? `Kirim ulang dalam ${wait} detik` : 'Kirim Ulang OTP'">Kirim Ulang OTP</span></button>
        </form>
        <a href="{{ route('password.request') }}" class="mt-5 block text-center text-sm font-bold text-primary hover:text-secondary"><i class="fas fa-arrow-left mr-2"></i>Gunakan akun lain</a>
    </div>
</x-layouts.app>
