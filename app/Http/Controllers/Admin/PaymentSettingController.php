<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Services\Payments\PaymentConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function edit(PaymentConfiguration $payments): View
    {
        return view('admin.settings.payment', [
            'status' => $payments->status(),
            'callbackUrl' => route('payments.midtrans.notification'),
        ]);
    }

    public function update(Request $request, PaymentConfiguration $payments): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'environment' => ['required', 'in:sandbox,production'],
            'server_key' => ['nullable', 'string', 'max:255'],
            'client_key' => ['nullable', 'string', 'max:255'],
            'merchant_id' => ['nullable', 'string', 'max:100'],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi admin tidak sesuai.',
        ]);

        $setting = PaymentSetting::query()->firstOrNew(['provider' => 'midtrans']);
        $serverKey = filled($validated['server_key'] ?? null)
            ? trim($validated['server_key'])
            : ($setting->server_key ?: config('services.midtrans.server_key'));
        $clientKey = filled($validated['client_key'] ?? null)
            ? trim($validated['client_key'])
            : ($setting->client_key ?: config('services.midtrans.client_key'));
        $isProduction = $validated['environment'] === 'production';

        $this->validateCredentials(
            $request->boolean('is_active'),
            $isProduction,
            $serverKey,
            $clientKey,
        );

        DB::transaction(function () use ($request, $setting, $validated, $isProduction) {
            $setting->fill([
                'is_active' => $request->boolean('is_active'),
                'is_production' => $isProduction,
                'merchant_id' => filled($validated['merchant_id'] ?? null)
                    ? trim($validated['merchant_id'])
                    : null,
                'updated_by' => $request->user()->id,
            ]);

            if (filled($validated['server_key'] ?? null)) {
                $setting->server_key = trim($validated['server_key']);
            }

            if (filled($validated['client_key'] ?? null)) {
                $setting->client_key = trim($validated['client_key']);
            }

            $setting->save();
        });

        $payments->reset();

        return back()->with('success', 'Konfigurasi Midtrans berhasil diperbarui.');
    }

    private function validateCredentials(
        bool $isActive,
        bool $isProduction,
        ?string $serverKey,
        ?string $clientKey,
    ): void {
        $errors = [];

        if ($isActive && blank($serverKey)) {
            $errors['server_key'] = 'Server Key wajib tersedia sebelum Midtrans diaktifkan.';
        }

        if ($isActive && blank($clientKey)) {
            $errors['client_key'] = 'Client Key wajib tersedia sebelum Midtrans diaktifkan.';
        }

        if (filled($serverKey) && $isProduction === str_starts_with($serverKey, 'SB-')) {
            $errors['server_key'] = $isProduction
                ? 'Server Key Sandbox tidak dapat dipakai pada mode Production.'
                : 'Mode Sandbox harus menggunakan Server Key Sandbox (awalan SB-).';
        }

        if (filled($clientKey) && $isProduction === str_starts_with($clientKey, 'SB-')) {
            $errors['client_key'] = $isProduction
                ? 'Client Key Sandbox tidak dapat dipakai pada mode Production.'
                : 'Mode Sandbox harus menggunakan Client Key Sandbox (awalan SB-).';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
