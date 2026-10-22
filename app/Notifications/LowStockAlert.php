<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(public ProductVariant $variant) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->variant->loadMissing('product');

        return (new MailMessage)
            ->subject('Low stock alert: '.$this->variant->product->name)
            ->greeting('Low stock alert')
            ->line('A product variant has reached its configured stock threshold.')
            ->line('Product: '.$this->variant->product->name)
            ->line('Option: '.$this->variant->optionLabel())
            ->line('Current stock: '.(int) $this->variant->stock_quantity)
            ->line('Threshold: '.(int) $this->variant->stock_threshold)
            ->action('Review product', url('/admin/products/'.$this->variant->product_id.'/edit'));
    }
}
