<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use Audited;

    protected $table = 'leads';

    protected $fillable = ['company', 'contact_person', 'phone', 'email', 'message', 'source', 'assigned_to', 'client_id', 'received_at', 'sla_due_at', 'contacted_at', 'sla_alerted_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'sla_due_at' => 'datetime', 'contacted_at' => 'datetime', 'sla_alerted_at' => 'datetime'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
