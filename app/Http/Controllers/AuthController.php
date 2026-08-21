<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use App\Services\TurnstileVerifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    private const REGISTRATION_SESSION = 'registration_otp';

    private const OTP_EXPIRES_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_MAX_RESENDS = 3;

    private const OTP_RESEND_COOLDOWN = 60;

    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, TurnstileVerifier $turnstile): RedirectResponse
    {
        $request->authenticate($turnstile);
        $request->session()->regenerate();

        return $request->user()->isAdmin()
            ? redirect()->intended(route('admin.dashboard'))
            : redirect()->intended(route('buyer.dashboard'));
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request, TurnstileVerifier $turnstile): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'Email sudah digunakan akun lain. Silakan masuk atau gunakan email berbeda.',
            'phone.unique' => 'Nomor HP sudah digunakan akun lain. Silakan gunakan nomor berbeda.',
        ]);

        $turnstile->verify($request, 'register');
        $code = $this->generateOtp();

        try {
            $this->sendRegistrationOtp($validated['email'], $validated['name'], $code);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'Kode OTP belum dapat dikirim. Periksa alamat email atau coba kembali beberapa saat lagi.',
            ])->onlyInput('name', 'email', 'phone');
        }

        $request->session()->put(self::REGISTRATION_SESSION, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
            'resends' => 0,
        ]);

        return redirect()->route('register.otp.notice')->with(
            'success',
            'Kode OTP telah dikirim ke '.$validated['email'].'. Periksa kotak masuk atau folder spam.'
        );
    }

    public function showRegistrationOtp(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::REGISTRATION_SESSION);

        if (! $pending) {
            return redirect()->route('register')->withErrors([
                'email' => 'Sesi pendaftaran tidak ditemukan. Silakan isi formulir kembali.',
            ]);
        }

        return view('auth.verify-registration-otp', [
            'maskedEmail' => $this->maskEmail($pending['email']),
            'canResendIn' => max(0, self::OTP_RESEND_COOLDOWN - (now()->timestamp - $pending['sent_at'])),
        ]);
    }

    public function verifyRegistrationOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'Kode OTP harus terdiri dari enam digit angka.',
        ]);

        $pending = $request->session()->get(self::REGISTRATION_SESSION);

        if (! $pending) {
            return redirect()->route('register')->withErrors([
                'email' => 'Sesi pendaftaran tidak ditemukan. Silakan daftar ulang.',
            ]);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::REGISTRATION_SESSION);

            return redirect()->route('register')->withErrors([
                'email' => 'Kode OTP sudah kedaluwarsa. Silakan daftar ulang.',
            ]);
        }

        if (! Hash::check((string) $request->input('code'), $pending['code'])) {
            $pending['attempts']++;

            if ($pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
                $request->session()->forget(self::REGISTRATION_SESSION);

                return redirect()->route('register')->withErrors([
                    'email' => 'Batas percobaan OTP tercapai. Silakan mulai pendaftaran kembali.',
                ]);
            }

            $request->session()->put(self::REGISTRATION_SESSION, $pending);

            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah. Sisa percobaan: '.(self::OTP_MAX_ATTEMPTS - $pending['attempts']).'.',
            ]);
        }

        if (User::where('email', $pending['email'])->orWhere('phone', $pending['phone'])->exists()) {
            $request->session()->forget(self::REGISTRATION_SESSION);

            return redirect()->route('register')->withErrors([
                'email' => 'Email atau nomor HP sudah digunakan akun lain.',
            ]);
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $pending['phone'],
            'password' => $pending['password'],
            'role' => UserRole::Buyer,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $request->session()->forget(self::REGISTRATION_SESSION);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('buyer.dashboard')->with(
            'success',
            'Email terverifikasi dan akun pembeli berhasil dibuat.'
        );
    }

    public function resendRegistrationOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::REGISTRATION_SESSION);

        if (! $pending || now()->timestamp > $pending['expires_at']) {
            $request->session()->forget(self::REGISTRATION_SESSION);

            return redirect()->route('register')->withErrors([
                'email' => 'Sesi OTP berakhir. Silakan daftar ulang.',
            ]);
        }

        if ($pending['resends'] >= self::OTP_MAX_RESENDS) {
            $request->session()->forget(self::REGISTRATION_SESSION);

            return redirect()->route('register')->withErrors([
                'email' => 'Batas kirim ulang OTP tercapai. Silakan daftar ulang.',
            ]);
        }

        $elapsed = now()->timestamp - $pending['sent_at'];
        if ($elapsed < self::OTP_RESEND_COOLDOWN) {
            return back()->withErrors([
                'code' => 'Tunggu '.(self::OTP_RESEND_COOLDOWN - $elapsed).' detik sebelum meminta kode baru.',
            ]);
        }

        $code = $this->generateOtp();

        try {
            $this->sendRegistrationOtp($pending['email'], $pending['name'], $code);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'code' => 'Kode baru belum dapat dikirim. Silakan coba kembali.',
            ]);
        }

        $pending['code'] = Hash::make($code);
        $pending['expires_at'] = now()->addMinutes(self::OTP_EXPIRES_MINUTES)->timestamp;
        $pending['sent_at'] = now()->timestamp;
        $pending['attempts'] = 0;
        $pending['resends']++;
        $request->session()->put(self::REGISTRATION_SESSION, $pending);

        return back()->with('success', 'Kode OTP baru telah dikirim ke '.$pending['email'].'.');
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendRegistrationOtp(string $email, string $name, string $code): void
    {
        Notification::route('mail', [$email => $name])
            ->notify(new RegistrationOtpNotification($code, $name, self::OTP_EXPIRES_MINUTES));
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }
}
