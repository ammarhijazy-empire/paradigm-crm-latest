<?php

namespace App\Mail;

use App\Jobs\SendOutboxEmail;
use App\Models\EmailOutbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/** Every email the CRM sends is written to email_outbox first and sent by a queued job. */
class OutboxMailer
{
    public function queue(string $to, string $subject, string $view, array $data, string $kind, ?Model $related = null, ?User $user = null, string $locale = 'en', array $cc = [], array $attachments = []): EmailOutbox
    {
        $prev = App::getLocale();
        App::setLocale($locale);
        try {
            $html = view($view, $data + ['locale' => $locale])->render();
        } finally {
            App::setLocale($prev);
        }
        $row = EmailOutbox::create([
            'to' => $to, 'cc' => $cc, 'subject' => $subject, 'body_html' => $html, 'attachments' => $attachments,
            'kind' => $kind, 'related_type' => $related?->getMorphClass(), 'related_id' => $related?->getKey(),
            'status' => 'queued', 'created_by' => $user?->id,
        ]);
        SendOutboxEmail::dispatch($row->id)->afterCommit();
        return $row;
    }
}
