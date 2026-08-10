<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderEnquirySubmitted extends Notification
{
    use Queueable;

    public function __construct(public array $summary) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your order enquiry has been received')
            ->greeting('Thank you for your enquiry!')
            ->line('We have received your order enquiry and our team will reply shortly with next steps and payment instructions.')
            ->line('Shipping to: '.$this->summary['name'].', '.$this->summary['address'].', '.$this->summary['city'].', '.$this->summary['province'].' '.$this->summary['postal_code'].', '.$this->summary['country'])
            ->line('Phone: '.($this->summary['phone'] ?: 'Not provided'));

        if (! empty($this->summary['items'])) {
            $mail->line('Proposed order:')
                ->line('Subtotal: $'.$this->summary['subtotal'].' CAD');
            foreach ($this->summary['items'] as $item) {
                $mail->line("• {$item['name']} ({$item['option']}) × {$item['quantity']} — \${$item['line_total']} CAD");
            }
            $mail->line('Estimated total: $'.$this->summary['total'].' CAD');
        }

        if (! empty($this->summary['notes'])) {
            $mail->line('Your notes: '.$this->summary['notes']);
        }

        return $mail->line('Thank you for shopping with Leather Wallets Canada.');
    }
}
