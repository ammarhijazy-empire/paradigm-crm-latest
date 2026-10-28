<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use Audited;

    protected $table = 'vendors';

    protected $fillable = ['name', 'type', 'country', 'contact_person', 'email', 'phone', 'notes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(VendorQuote::class);
    }
}
