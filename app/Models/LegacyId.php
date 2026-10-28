<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyId extends Model
{
    protected $table = 'legacy_ids';

    protected $fillable = ['legacy_table', 'legacy_id', 'model', 'new_id'];
}
