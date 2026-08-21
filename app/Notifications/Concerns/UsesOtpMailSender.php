<?php

namespace App\Notifications\Concerns;

trait UsesOtpMailSender
{
    /** @return array{0: string, 1: string} */
    private function otpSender(): array
    {
        $smtpUsername = (string) config('mail.mailers.smtp.username', '');
        $globalAddress = (string) config('mail.from.address', '');
        $address = filter_var($smtpUsername, FILTER_VALIDATE_EMAIL) ? $smtpUsername : $globalAddress;

        return [
            $address ?: 'no-reply@sipaduhok.id',
            (string) config('mail.from.name', config('app.name', 'Toko Sipaduhok')),
        ];
    }
}
