<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TurnstileVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * @throws ValidationException
     */
    public function verify(Request $request, string $expectedAction): void
    {
        $siteKey = (string) config('services.turnstile.site_key');
        $secretKey = (string) config('services.turnstile.secret_key');

        if ($siteKey === '' && $secretKey === '') {
            return;
        }

        $token = (string) $request->input('cf-turnstile-response', '');

        if ($siteKey === '' || $secretKey === '' || $token === '' || mb_strlen($token) > 2048) {
            $this->fail();
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(8)
                ->post(self::VERIFY_URL, [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException $exception) {
            report($exception);
            $this->fail('Layanan verifikasi keamanan sedang tidak tersedia. Silakan coba kembali.');
        }

        $hostname = trim((string) config('services.turnstile.hostname'));
        $valid = $response->successful()
            && $response->json('success') === true
            && hash_equals($expectedAction, (string) $response->json('action'))
            && ($hostname === '' || hash_equals($hostname, (string) $response->json('hostname')));

        if (! $valid) {
            Log::warning('Cloudflare Turnstile verification rejected.', [
                'action' => $expectedAction,
                'hostname' => $response->json('hostname'),
                'error_codes' => $response->json('error-codes', []),
                'ip' => $request->ip(),
            ]);

            $this->fail();
        }
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message = 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba kembali.'): never
    {
        throw ValidationException::withMessages([
            'cf-turnstile-response' => $message,
        ]);
    }
}
