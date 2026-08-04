<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderSubmitted extends Notification
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
            ->subject("Order {$this->order->order_number} received")
            ->greeting("Hello {$this->order->user->name},")
            ->line($this->order->payment_method === 'paypal'
                ? 'We received your wholesale order. You selected payment through PayPal.'
                : 'We received your wholesale order enquiry. No online payment has been taken.')
            ->line('Product subtotal: $'.$this->order->subtotal.' '.$this->order->currency)
            ->line('Standard Shipping ('.$this->order->shipping_service.'): $'.$this->order->shipping_total.' '.$this->order->currency)
            ->line('Order total: $'.$this->order->total.' '.$this->order->currency)
            ->action('View order', route('orders.show', $this->order))
            ->line('Thank you for choosing IGI Canada.');
    }
}
