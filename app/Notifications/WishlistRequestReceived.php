<?php

namespace App\Notifications;

use App\Models\WishlistRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WishlistRequestReceived extends Notification
{
    use Queueable;

    public function __construct(public WishlistRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request->loadMissing('product', 'variant');
        $channel = $request->sales_channel === 'retail' ? 'Retail' : 'Wholesale';

        return (new MailMessage)
            ->subject("Notify Me request: {$request->product->name}")
            ->line("{$channel} customer requested an availability notification.")
            ->line('Customer: '.($request->name ?: 'Not provided'))
            ->line('Email: '.($request->email ?: 'Not provided'))
            ->line('Product: '.$request->product->name)
            ->line('Option: '.($request->variant?->optionLabel() ?: 'Standard'))
            ->action('Review request', url('/admin/wishlist-requests/'.$request->id.'/edit'));
    }
}
