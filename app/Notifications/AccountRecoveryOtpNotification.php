<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRecoveryOtpNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly int $expiresMinutes = 10,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Pemulihan Akun Toko Sipaduhok')
            ->greeting('Halo '.$this->name.',')
            ->line('Kami menerima permintaan pemulihan akun Anda. Gunakan kode OTP berikut:')
            ->line('**'.$this->code.'**')
            ->line('Kode berlaku selama '.$this->expiresMinutes.' menit dan hanya dapat digunakan satu kali.')
            ->line('Jangan membagikan kode ini. Admin Toko Sipaduhok tidak pernah meminta kode OTP Anda.')
            ->line('Jika Anda tidak meminta pemulihan, abaikan email ini dan kata sandi Anda tetap aman.')
            ->salutation('Salam, Toko Sipaduhok');
    }
}
