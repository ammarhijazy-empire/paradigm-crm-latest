<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Exceptions\WorkflowException;
use App\Models\Client;
use App\Models\FilePayTerm;
use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Turns a validated quote request into a new shipment file and sends it to pricing. */
class RfqService
{
    public function __construct(private FileStageService $stages) {}

    public function create(array $data, User $sales, bool $sendToPricing): ShipmentFile
    {
        $client = Client::findOrFail($data['client_id']); // global scope: sales can only pick their own clients
        if ($sendToPricing && ($p = InquiryValidator::problems($data))) {
            throw new WorkflowException(array_map(fn ($x) => __($x[0], $x[1]), $p));
        }

        return DB::transaction(function () use ($data, $sales, $client, $sendToPricing) {
            $f = new ShipmentFile([
                'number' => RefGenerator::fileNumber(),
                'ref' => RefGenerator::salesRef($sales),
                'client_id' => $client->id,
                'sales_id' => $sales->hasRole('sales') ? $sales->id : $client->owner_id,
                'direction' => $data['direction'] ?? 'import',
                'service' => $data['service'],
                'addons' => array_values($data['addons'] ?? []),
                'origin' => $data['origin'] ?? null,
                'destination' => $data['destination'] ?? null,
                'incoterm' => $data['incoterm'] ?? null,
                'pickup_address' => $data['pickup_address'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'inq' => $data['inq'] ?? [],
                'pricing_status' => $sendToPricing ? 'pending' : 'draft',
            ]);
            $this->stages->initialize($f, $sales);

            $pay = $data['inq']['pay'] ?? 'cash_in_advance';
            FilePayTerm::create([
                'file_id' => $f->id, 'method' => $pay, 'credit_days' => $data['inq']['credit_days'] ?? null,
                'status' => in_array($pay, ['on_arrival', 'credit'], true) ? 'pending' : 'not_needed',
            ]);

            if ($sendToPricing) {
                $this->stages->history($f, $sales, 'sent_to_pricing');
                $this->stages->advance($f, $sales);
                event(new CrmEvent('rfq_to_price', ['ref' => $f->ref], route('files.show', $f), ['pricing'], [], $sales));
            }
            if (in_array($pay, ['on_arrival', 'credit'], true)) {
                event(new CrmEvent('pay_terms_needed', ['ref' => $f->ref], route('files.show', $f), ['accounts'], [], $sales));
            }
            return $f;
        });
    }

    public function sendDraft(ShipmentFile $f, User $user): void
    {
        if ($p = InquiryValidator::forFile($f)) {
            throw new WorkflowException(array_map(fn ($x) => __($x[0], $x[1]), $p));
        }
        $f->update(['pricing_status' => 'pending']);
        $this->stages->history($f, $user, 'sent_to_pricing');
        if ($f->stage === 1) {
            $this->stages->advance($f, $user);
        }
        event(new CrmEvent('rfq_to_price', ['ref' => $f->ref], route('files.show', $f), ['pricing'], [], $user));
    }
}
