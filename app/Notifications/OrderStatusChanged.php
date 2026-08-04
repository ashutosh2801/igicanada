<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = str($this->order->status)->replace('_', ' ')->title();
        $message = (new MailMessage)
            ->subject("Order {$this->order->order_number}: {$status}")
            ->greeting("Hello {$this->order->user->name},")
            ->line("Your order status is now {$status}.")
            ->line('Current total: $'.$this->order->total.' '.$this->order->currency);

        if ($this->order->tracking_number) {
            $message->line("Tracking number: {$this->order->tracking_number}");
        }

        return $message
            ->action('View order', route('orders.show', $this->order))
            ->line('Please contact IGI Canada if you have any questions.');
    }
}
