<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class MandrillFallBack extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param string $subject
     * @param array<string, mixed> $data
     */
    public function __construct(public $subject, private array $data)
    {
    }

    /**
     * Get the url content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.mandrill_fallback',
            with: ['data' => $this->data],
        );
    }
}
