<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $table = 'ports';

    protected $fillable = ['code', 'name_en', 'name_ar', 'country', 'kind', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
