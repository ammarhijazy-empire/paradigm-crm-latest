<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Models\Lead;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class LeadService
{
    /** Next salesperson in rotation: the active lead-taker whose latest lead is the oldest (or who has none). */
    public function nextAssignee(): ?User
    {
        return User::role('sales')->where('active', true)->where('gets_leads', true)
            ->orderByRaw('(select coalesce(max(l.id), 0) from leads l where l.assigned_to = users.id) asc')->orderBy('id')->first();
    }

    public function receive(array $d): Lead
    {
        return DB::transaction(function () use ($d) {
            $to = User::role('sales')->where('active', true)->where('gets_leads', true)
                ->orderByRaw('(select coalesce(max(l.id), 0) from leads l where l.assigned_to = users.id) asc')->orderBy('id')->lockForUpdate()->first();
            $lead = Lead::create([
                'company' => $d['company'], 'contact_person' => $d['contact_person'] ?? null, 'phone' => $d['phone'] ?? null,
                'email' => $d['email'] ?? null, 'message' => $d['message'] ?? null, 'source' => $d['source'] ?? 'website',
                'assigned_to' => $to?->id, 'received_at' => now(), 'sla_due_at' => now()->addHours((int) Settings::get('lead_sla_hours')),
            ]);
            if ($to) {
                $to->forceFill(['last_lead_at' => now()])->save();
            }
            event(new CrmEvent('new_lead', ['company' => $lead->company, 'to' => $to?->name ?? '—'], route('leads.index'), [], array_filter([$to?->id])));
            return $lead;
        });
    }

    public function markContacted(Lead $lead, User $user): void
    {
        abort_unless($user->isManagement() || $lead->assigned_to === $user->id, 403);
        $lead->update(['contacted_at' => now()]);
    }

    public function reassign(Lead $lead, User $by, User $to): void
    {
        abort_unless($by->isManagement(), 403);
        $lead->update(['assigned_to' => $to->id]);
    }
}
