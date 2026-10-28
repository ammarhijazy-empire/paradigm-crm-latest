<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOutbox extends Model
{
    protected $table = 'email_outbox';

    protected $fillable = ['to', 'cc', 'subject', 'body_html', 'attachments', 'kind', 'related_type', 'related_id', 'status', 'attempts', 'error', 'sent_at', 'created_by'];

    protected function casts(): array
    {
        return ['cc' => 'array', 'attachments' => 'array', 'sent_at' => 'datetime'];
    }
}
