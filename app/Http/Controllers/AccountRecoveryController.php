<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountRecoveryOtpNotification;
use App\Services\TurnstileVerifier;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AccountRecoveryController extends Controller
{
    private const OTP_SESSION = 'account_recovery_otp';

    private const AUTHORIZED_SESSION = 'account_recovery_authorized';

    private const OTP_EXPIRES_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_MAX_RESENDS = 3;

    private const OTP_RESEND_COOLDOWN = 60;

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, TurnstileVerifier $turnstile): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Masukkan email atau nomor HP akun.',
        ]);

        $turnstile->verify($request, 'recovery');
        $identifier = trim($validated['identifier']);
        $user = User::query()
            ->where('email', Str::lower($identifier))
            ->orWhere('phone', $identifier)
            ->first();

        $code = $this->generateOtp();

        if ($user) {
            try {
                $this->sendRecoveryOtp($user, $code);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $request->session()->forget(self::AUTHORIZED_SESSION);
        $request->session()->put(self::OTP_SESSION, [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'masked_email' => $user ? $this->maskEmail($user->email) : 'email akun terdaftar',
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
            'resends' => 0,
        ]);

        return redirect()->route('recovery.otp.notice')->with(
            'success',
            'Jika data cocok, kode OTP pemulihan telah dikirim ke email akun terdaftar.'
        );
    }

    public function showOtp(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::OTP_SESSION);

        if (! $pending) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Sesi pemulihan tidak ditemukan. Silakan mulai kembali.',
            ]);
        }

        return view('auth.verify-recovery-otp', [
            'maskedEmail' => $pending['masked_email'],
            'canResendIn' => max(0, self::OTP_RESEND_COOLDOWN - (now()->timestamp - $pending['sent_at'])),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'Kode OTP harus terdiri dari enam digit angka.',
        ]);

        $pending = $request->session()->get(self::OTP_SESSION);

        if (! $pending) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Sesi pemulihan tidak ditemukan. Silakan mulai kembali.',
            ]);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::OTP_SESSION);

            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.',
            ]);
        }

        if (! Hash::check((string) $request->input('code'), $pending['code'])) {
            $pending['attempts']++;

            if ($pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
                $request->session()->forget(self::OTP_SESSION);

                return redirect()->route('password.request')->withErrors([
                    'identifier' => 'Batas percobaan OTP tercapai. Silakan mulai pemulihan kembali.',
                ]);
            }

            $request->session()->put(self::OTP_SESSION, $pending);

            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah. Sisa percobaan: '.(self::OTP_MAX_ATTEMPTS - $pending['attempts']).'.',
            ]);
        }

        if (! $pending['user_id'] || ! User::whereKey($pending['user_id'])->where('email', $pending['email'])->exists()) {
            $request->session()->forget(self::OTP_SESSION);

            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Akun tidak lagi tersedia.',
            ]);
        }

        $request->session()->forget(self::OTP_SESSION);
        $request->session()->regenerate();
        $request->session()->put(self::AUTHORIZED_SESSION, [
            'user_id' => $pending['user_id'],
            'email' => $pending['email'],
            'expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES)->timestamp,
        ]);

        return redirect()->route('recovery.password.edit');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::OTP_SESSION);

        if (! $pending || now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::OTP_SESSION);

            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Sesi OTP berakhir. Silakan mulai pemulihan kembali.',
            ]);
        }

        if ($pending['resends'] >= self::OTP_MAX_RESENDS) {
            $request->session()->forget(self::OTP_SESSION);

            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Batas kirim ulang OTP tercapai. Silakan mulai kembali.',
            ]);
        }

        $elapsed = now()->timestamp - $pending['sent_at'];
        if ($elapsed < self::OTP_RESEND_COOLDOWN) {
            return back()->withErrors([
                'code' => 'Tunggu '.(self::OTP_RESEND_COOLDOWN - $elapsed).' detik sebelum meminta kode baru.',
            ]);
        }

        $user = $pending['user_id'] ? User::find($pending['user_id']) : null;

        $code = $this->generateOtp();

        if ($user) {
            try {
                $this->sendRecoveryOtp($user, $code);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $pending['code'] = Hash::make($code);
        $pending['expires_at'] = now()->addMinutes(self::OTP_EXPIRES_MINUTES)->timestamp;
        $pending['sent_at'] = now()->timestamp;
        $pending['attempts'] = 0;
        $pending['resends']++;
        $request->session()->put(self::OTP_SESSION, $pending);

        return back()->with('success', 'Jika data cocok, kode OTP baru telah dikirim ke email akun terdaftar.');
    }

    public function editPassword(Request $request): View|RedirectResponse
    {
        $authorized = $this->authorizedRecovery($request);

        if (! $authorized) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Verifikasi pemulihan sudah berakhir. Silakan mulai kembali.',
            ]);
        }

        return view('auth.reset-password', ['email' => $authorized['email']]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $authorized = $this->authorizedRecovery($request);

        if (! $authorized) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Verifikasi pemulihan sudah berakhir. Silakan mulai kembali.',
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::whereKey($authorized['user_id'])->where('email', $authorized['email'])->first();
        if (! $user) {
            $request->session()->forget(self::AUTHORIZED_SESSION);

            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Akun tidak lagi tersedia.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        event(new PasswordReset($user));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'success',
            'Kata sandi berhasil diperbarui. Email akun Anda: '.$user->email
        );
    }

    /** @return array<string, mixed>|null */
    private function authorizedRecovery(Request $request): ?array
    {
        $authorized = $request->session()->get(self::AUTHORIZED_SESSION);

        if (! $authorized || now()->timestamp > $authorized['expires_at']) {
            $request->session()->forget(self::AUTHORIZED_SESSION);

            return null;
        }

        return $authorized;
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendRecoveryOtp(User $user, string $code): void
    {
        Notification::route('mail', $user->email)
            ->notify(new AccountRecoveryOtpNotification($code, $user->name, self::OTP_EXPIRES_MINUTES));

        logger()->info('Recovery OTP mail accepted by mailer.', [
            'to' => Str::mask($user->email, '*', 3, -8),
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'scheme' => config('mail.mailers.smtp.scheme'),
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
