<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesOtpMailSender;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
{
    use UsesOtpMailSender;

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
        $appName = config('app.name', 'Toko Sipaduhok');
        [$fromAddress, $fromName] = $this->otpSender();

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject('Kode OTP Pendaftaran '.$appName)
            ->greeting('Halo '.$this->name.',')
            ->line('Gunakan kode OTP berikut untuk menyelesaikan pendaftaran akun pembeli:')
            ->line('**'.$this->code.'**')
            ->line('Kode berlaku selama '.$this->expiresMinutes.' menit dan hanya dapat digunakan satu kali.')
            ->line('Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai admin toko.')
            ->line('Jika Anda tidak melakukan pendaftaran, abaikan email ini.')
            ->salutation('Salam, Toko Sipaduhok');
    }
}
