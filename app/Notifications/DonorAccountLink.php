<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonorAccountLink extends Notification
{
    public function __construct(public string $token, public bool $activation = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->activation ? 'Aktivasi akun donatur tetap' : 'Reset password donatur';

        return (new MailMessage)
            ->subject($title.' — An-Nuur 2')
            ->greeting('Halo, '.$notifiable->name.'.')
            ->line($this->activation
                ? 'Pengurus mengundang Anda mengaktifkan akun donatur tetap. Tetapkan password untuk memverifikasi email dan mengaktifkan akun.'
                : 'Kami menerima permintaan pemulihan password akun donatur Anda.')
            ->action($title, route('donor.password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Tautan berlaku selama '.config('auth.passwords.donors.expire').' menit dan hanya dapat digunakan sekali.')
            ->line('Jika Anda tidak mengenali permintaan ini, abaikan email atau hubungi pengurus.');
    }
}
