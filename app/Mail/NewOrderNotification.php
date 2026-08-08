<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal "you've got a sale" alert, sent to the shop when payment confirms.
 * Replying goes to the customer, so an order query can be answered directly
 * from the notification.
 */
class NewOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $total = number_format($this->order->total_cents / 100, 2);

        return new Envelope(
            subject: "New order {$this->order->number} — €{$total}",
            replyTo: [$this->order->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-order',
        );
    }
}
