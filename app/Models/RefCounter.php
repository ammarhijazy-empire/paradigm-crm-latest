<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefCounter extends Model
{
    protected $table = 'ref_counters';

    protected $fillable = ['key', 'value'];
}
