<?php

namespace App\Notifications;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetailOrderSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('items');

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $this->order,
            'company' => config('commerce.company'),
        ])->setPaper('letter')->output();

        return (new MailMessage)
            ->subject("Leather Wallets order {$this->order->order_number} received")
            ->greeting('Thank you for your order!')
            ->line($this->order->payment_method === 'paypal'
                ? 'Your order has been created and is awaiting PayPal payment.'
                : 'Your order has been received. Our team will contact you with payment instructions.')
            ->line('Order total: $'.$this->order->total.' '.$this->order->currency)
            ->line('Order number: '.$this->order->order_number)
            ->attachData($pdf, "IGI-Canada-{$this->order->order_number}.pdf", [
                'mime' => 'application/pdf',
            ])
            ->line('Thank you for shopping with Leather Wallets Canada.');
    }
}
