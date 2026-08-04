<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyWholesaleEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your IGI Canada wholesale account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Thank you for applying for an IGI Canada wholesale account.')
            ->line('Please verify your business email address before we review your application.')
            ->action('Verify wholesale account', $this->verificationUrl($notifiable))
            ->line('This verification link will expire in '.config('auth.verification.expire', 60).' minutes.')
            ->line('After verification, your application will remain pending until it is approved by IGI Canada.');
    }
}
