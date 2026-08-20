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
            'callbackUrl' => route('payments.paywuz.webhook'),
        ]);
    }

    public function update(Request $request, PaymentConfiguration $payments): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'environment' => ['required', 'in:sandbox,production'],
            'sandbox_api_key' => ['nullable', 'string', 'max:255'],
            'production_api_key' => ['nullable', 'string', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi admin tidak sesuai.',
        ]);

        $setting = PaymentSetting::query()->firstOrNew(['provider' => 'paywuz']);
        $sandboxApiKey = filled($validated['sandbox_api_key'] ?? null)
            ? trim($validated['sandbox_api_key'])
            : ($setting->sandbox_api_key ?: config('services.paywuz.sandbox_api_key'));
        $productionApiKey = filled($validated['production_api_key'] ?? null)
            ? trim($validated['production_api_key'])
            : ($setting->production_api_key ?: config('services.paywuz.production_api_key'));
        $isProduction = $validated['environment'] === 'production';

        $this->validateCredentials(
            $request->boolean('is_active'),
            $isProduction,
            $sandboxApiKey,
            $productionApiKey,
        );

        DB::transaction(function () use ($request, $setting, $validated, $isProduction) {
            $setting->fill([
                'is_active' => $request->boolean('is_active'),
                'is_production' => $isProduction,
                'updated_by' => $request->user()->id,
            ]);

            if (filled($validated['sandbox_api_key'] ?? null)) {
                $setting->sandbox_api_key = trim($validated['sandbox_api_key']);
            }

            if (filled($validated['production_api_key'] ?? null)) {
                $setting->production_api_key = trim($validated['production_api_key']);
            }

            $setting->save();
        });

        $payments->reset();

        return back()->with('success', 'Konfigurasi Paywuz berhasil diperbarui.');
    }

    private function validateCredentials(
        bool $isActive,
        bool $isProduction,
        ?string $sandboxApiKey,
        ?string $productionApiKey,
    ): void {
        $errors = [];

        $activeKey = $isProduction ? $productionApiKey : $sandboxApiKey;
        $field = $isProduction ? 'production_api_key' : 'sandbox_api_key';
        $prefix = $isProduction ? 'pk_live_' : 'pk_sand_';

        if ($isActive && blank($activeKey)) {
            $errors[$field] = 'API key untuk environment aktif wajib tersedia sebelum Paywuz diaktifkan.';
        }

        if ($isActive && filled($activeKey) && ! preg_match('/^'.preg_quote($prefix, '/').'[a-f0-9]{32}$/i', $activeKey)) {
            $errors[$field] = "API key environment ini harus berformat {$prefix} diikuti 32 karakter heksadesimal.";
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
