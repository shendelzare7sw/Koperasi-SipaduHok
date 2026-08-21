<?php

use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('otp:test-mail {email}', function (string $email): int {
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Alamat email tidak valid.');

        return Command::FAILURE;
    }

    $mask = fn (?string $value): string => filled($value)
        ? Str::mask((string) $value, '*', 3, max(0, strlen((string) $value) - 6))
        : '-';

    $this->line('Konfigurasi mail aktif:');
    $this->line('  config cached : '.(app()->configurationIsCached() ? 'ya' : 'tidak'));
    $this->line('  mailer        : '.config('mail.default'));
    $this->line('  host          : '.config('mail.mailers.smtp.host'));
    $this->line('  port          : '.config('mail.mailers.smtp.port'));
    $this->line('  scheme        : '.(config('mail.mailers.smtp.scheme') ?: '-'));
    $this->line('  encryption    : '.(env('MAIL_ENCRYPTION') ?: '-'));
    $this->line('  username      : '.$mask(config('mail.mailers.smtp.username')));
    $this->line('  from          : '.config('mail.from.address').' / '.config('mail.from.name'));

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    try {
        Notification::route('mail', $email)
            ->notify(new RegistrationOtpNotification($code, 'Tes SMTP', 10));
    } catch (Throwable $exception) {
        report($exception);
        $this->error('SMTP menolak pengiriman: '.$exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Mailer menerima pengiriman OTP test.');
    $this->line('Kode test: '.$code);
    $this->line('Jika tidak masuk inbox/spam/all mail, berarti masalahnya sudah di sisi provider email atau reputasi pengiriman, bukan lagi exception Laravel.');

    return Command::SUCCESS;
})->purpose('Send a real OTP email and print sanitized SMTP diagnostics');
