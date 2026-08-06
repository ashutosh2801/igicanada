<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetailPaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received for {$this->order->order_number}")
            ->greeting('Payment received')
            ->line('Your Leather Wallets Canada order is now being prepared.')
            ->line('Paid total: $'.$this->order->total.' '.$this->order->currency)
            ->line('Order number: '.$this->order->order_number);
    }
}
