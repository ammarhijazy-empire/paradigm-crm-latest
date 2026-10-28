<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\ShipmentFile;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $u, Document $d): bool
    {
        if ($d->file_id) {
            $f = ShipmentFile::withoutGlobalScopes()->find($d->file_id);
            return $f && app(ShipmentFilePolicy::class)->view($u, $f);
        }
        return $d->uploaded_by === $u->id || ($u->hasRole('sales') && $d->client_id && \App\Models\Client::whereKey($d->client_id)->exists());
    }
}
