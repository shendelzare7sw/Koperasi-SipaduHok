<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_legacy_mail_encryption_ssl_maps_to_smtps_scheme(): void
    {
        $previousScheme = getenv('MAIL_SCHEME');
        $previousEncryption = getenv('MAIL_ENCRYPTION');

        putenv('MAIL_SCHEME');
        unset($_ENV['MAIL_SCHEME'], $_SERVER['MAIL_SCHEME']);
        putenv('MAIL_ENCRYPTION=ssl');
        $_ENV['MAIL_ENCRYPTION'] = 'ssl';
        $_SERVER['MAIL_ENCRYPTION'] = 'ssl';
        Env::enablePutenv();

        try {
            $mailConfig = require base_path('config/mail.php');

            $this->assertSame('smtps', $mailConfig['mailers']['smtp']['scheme']);
        } finally {
            $this->restoreEnv('MAIL_SCHEME', $previousScheme);
            $this->restoreEnv('MAIL_ENCRYPTION', $previousEncryption);
            Env::enablePutenv();
        }
    }

    private function restoreEnv(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
