<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Mail\OutboxMailer;
use App\Models\FileBooking;
use App\Models\FileUpdate;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Support\Carbon;

class ShippingService
{
    public function __construct(private FileStageService $stages, private OutboxMailer $mailer) {}

    public function saveBooking(ShipmentFile $f, array $d, User $user): void
    {
        abort_unless($user->actsAs('ops'), 403);
        FileBooking::updateOrCreate(['file_id' => $f->id], collect($d)->only(['vendor_id', 'booking_no', 'vessel_voyage', 'cutoff_at', 'cfs_warehouse', 'notes'])->all());
        $etaBefore = $f->eta?->toDateString();
        $f->fill(collect($d)->only(['carrier', 'bl_no', 'etd', 'atd', 'eta', 'acid_no'])->all());
        // ETA = ETD + transit time when ops gives no ETA of their own
        if (empty($d['eta']) && ($f->atd || $f->etd) && $f->quote_transit_days) {
            $f->eta = Carbon::parse($f->atd ?? $f->etd)->addDays($f->quote_transit_days);
        }
        $f->save();
        $this->stages->history($f, $user, 'shipping_saved');
        if ($etaBefore && $f->eta && $etaBefore !== $f->eta->toDateString()) {
            event(new CrmEvent('eta_changed', ['ref' => $f->number, 'eta' => $f->eta->format('d/m/Y')], route('files.show', $f), ['customs'], [$f->sales_id], $user));
        }
    }

    /** A client update (pre-alert, ETA change, arrival...) sent by email and logged on the file. */
    public function sendClientUpdate(ShipmentFile $f, User $user, string $kind, string $body, string $locale): FileUpdate
    {
        abort_unless($user->actsAs('ops') || $user->actsAs('customs') || $user->actsAs('sales'), 403);
        $client = $f->client;
        $out = $this->mailer->queue(
            to: $client->email,
            subject: __('shipping.update_subject.'.$kind, ['ref' => $f->number, 'bl' => $f->bl_no], $locale),
            view: 'emails.client-update',
            data: ['file' => $f, 'body' => $body, 'kind' => $kind],
            kind: $kind === 'prealert' ? 'prealert' : 'client_update',
            related: $f, user: $user, locale: $locale,
        );
        $u = FileUpdate::create(['file_id' => $f->id, 'user_id' => $user->id, 'kind' => $kind, 'channel' => 'email', 'body' => $body, 'email_outbox_id' => $out->id]);
        if ($kind === 'prealert' && $f->stage === 4 && $user->actsAs('ops') && ! $f->check('client_informed')) {
            $this->stages->tick($f, 'client_informed', true, $user);
        }
        return $u;
    }
}
