<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadKey extends Model
{
    protected $table = 'lead_keys';

    protected $fillable = ['name', 'key_hash', 'active', 'last_used_at'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'last_used_at' => 'datetime'];
    }
}
