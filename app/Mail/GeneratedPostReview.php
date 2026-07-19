<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class GeneratedPostReview extends Mailable
{
    use Queueable, SerializesModels;

    public string $rejectUrl;

    public function __construct(public Post $post)
    {
        // Signed, so the one-click link needs no login and can't be forged.
        $this->rejectUrl = URL::signedRoute('content.reject', ['post' => $post->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Review: generated post — {$this->post->title}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.generated-post-review');
    }
}
