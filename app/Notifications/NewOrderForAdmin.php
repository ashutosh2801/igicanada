<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderForAdmin extends Notification
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
            ->subject("New wholesale order {$this->order->order_number}")
            ->line("Wholesale account: {$this->order->user->name}")
            ->line('Checkout option: '.($this->order->payment_method === 'paypal' ? 'PayPal' : 'Order enquiry'))
            ->line('Product subtotal: $'.$this->order->subtotal.' '.$this->order->currency)
            ->line('Standard Shipping ('.$this->order->shipping_service.'): $'.$this->order->shipping_total.' '.$this->order->currency)
            ->line('Order total: $'.$this->order->total.' '.$this->order->currency)
            ->action('Review order', url('/admin/orders/'.$this->order->id.'/edit'));
    }
}
