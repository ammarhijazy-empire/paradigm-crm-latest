<?php

namespace App\Domain;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Support\Phone;

class ClientService
{
    /** Possible duplicates by normalised phone or email, across all salespeople. */
    public function duplicates(?string $phone, ?string $email, ?int $ignoreId = null)
    {
        $n = Phone::normalize($phone);
        $e = mb_strtolower(trim((string) $email));
        return Client::withoutGlobalScopes()->whereNull('deleted_at')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(fn ($q) => $q->when($n !== '', fn ($q) => $q->orWhere('phone_normalized', $n))->when($e !== '', fn ($q) => $q->orWhere('email', $e)))
            ->with('owner')->get(['id', 'company', 'owner_id', 'phone', 'email']);
    }

    public function create(array $d, User $user): Client
    {
        $c = new Client($d);
        $c->owner_id = $user->isManagement() && ! empty($d['owner_id']) ? $d['owner_id'] : $user->id;
        $c->status = $d['status'] ?? 'lead';
        $c->save();
        return $c;
    }

    public function update(Client $c, array $d, User $user): Client
    {
        if (! $user->isManagement()) {
            unset($d['owner_id']);
        }
        $c->update($d);
        return $c;
    }

    public function logActivity(Client $c, array $d, User $user): Activity
    {
        $a = Activity::create(['client_id' => $c->id, 'file_id' => $d['file_id'] ?? null, 'user_id' => $user->id, 'type' => $d['type'], 'body' => $d['body'], 'rating' => $d['rating'] ?? null, 'happened_at' => $d['happened_at'] ?? now()]);
        if ($c->status === 'lead') {
            $c->update(['status' => 'contacted']);
        }
        if (! empty($d['next_follow_up'])) {
            $c->update(['next_follow_up' => $d['next_follow_up']]);
        }
        return $a;
    }

    public function fromLead(Lead $lead, User $user, string $sector): Client
    {
        $c = new Client([
            'company' => $lead->company, 'contact_person' => $lead->contact_person ?: '—', 'phone' => $lead->phone ?: '—',
            'email' => $lead->email ?: '', 'sector' => $sector, 'source' => $lead->source, 'status' => 'contacted', 'next_follow_up' => now()->toDateString(),
        ]);
        $c->owner_id = $lead->assigned_to ?? $user->id;
        $c->save();
        $lead->update(['client_id' => $c->id, 'contacted_at' => $lead->contacted_at ?? now()]);
        return $c;
    }
}
