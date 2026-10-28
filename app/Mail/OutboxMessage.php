<?php

namespace App\Mail;

use App\Models\EmailOutbox;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OutboxMessage extends Mailable
{
    public function __construct(public EmailOutbox $row) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->row->subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->row->body_html);
    }

    public function attachments(): array
    {
        return collect($this->row->attachments ?? [])->map(fn ($a) => Attachment::fromStorageDisk('private', $a['path'])->as($a['name']))->all();
    }
}
