<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Exceptions\WorkflowException;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private FileStageService $stages) {}

    /** One tax invoice per currency, built from the file's selling lines. */
    public function createFromFile(ShipmentFile $f, User $user, array $vatByLineId, ?string $dueDate = null): array
    {
        abort_unless($user->actsAs('accounts'), 403);
        $lines = $f->quoteLines()->get();
        if ($lines->isEmpty()) {
            throw WorkflowException::one('invoice.no_lines');
        }
        $vatRate = (float) Settings::get('vat_rate') / 100;
        $days = (int) ($f->payTerms?->method === 'credit' ? ($f->payTerms->credit_days ?: 0) : 0);
        $created = [];
        DB::transaction(function () use ($f, $user, $lines, $vatByLineId, $vatRate, $days, $dueDate, &$created) {
            foreach ($lines->groupBy('currency') as $cur => $group) {
                if ($f->invoices()->where('currency', $cur)->where('status', '!=', 'cancelled')->exists()) {
                    continue;
                }
                $inv = Invoice::create([
                    'number' => RefGenerator::invoiceNumber(), 'file_id' => $f->id, 'client_id' => $f->client_id, 'currency' => $cur,
                    'issue_date' => now()->toDateString(), 'due_date' => $dueDate ?: now()->addDays($days)->toDateString(),
                    'status' => 'issued', 'created_by' => $user->id,
                ]);
                $sub = 0;
                $vat = 0;
                foreach ($group->values() as $i => $l) {
                    $applies = (bool) ($vatByLineId[$l->id] ?? true);
                    $amount = round((float) $l->amount, 2);
                    $v = $applies ? round($amount * $vatRate, 2) : 0;
                    InvoiceLine::create(['invoice_id' => $inv->id, 'description_en' => $l->description_en, 'description_ar' => $l->description_ar, 'qty' => 1, 'unit_price' => $amount, 'vat_applies' => $applies, 'vat_amount' => $v, 'amount' => $amount, 'sort' => $i]);
                    $sub += $amount;
                    $vat += $v;
                }
                $inv->update(['subtotal' => $sub, 'vat_total' => $vat, 'total' => round($sub + $vat, 2)]);
                $created[] = $inv;
            }
        });
        if ($created && $f->stage === 6 && ! $f->check('final_invoice')) {
            $this->stages->tick($f, 'final_invoice', true, $user);
        }
        foreach ($created as $inv) {
            $this->stages->history($f, $user, 'invoice_issued', null, null, $inv->number);
        }
        return $created;
    }

    public function setEInvoiceNo(Invoice $inv, User $user, ?string $no): void
    {
        abort_unless($user->actsAs('accounts'), 403);
        $inv->update(['e_invoice_no' => $no]);
    }

    public function recordPayment(Invoice $inv, User $user, float $amount, string $date, ?string $method, ?string $reference): Payment
    {
        abort_unless($user->actsAs('accounts'), 403);
        return DB::transaction(function () use ($inv, $user, $amount, $date, $method, $reference) {
            $inv = Invoice::whereKey($inv->id)->lockForUpdate()->first();
            if ($amount <= 0 || $amount > $inv->balance() + 0.01) {
                throw WorkflowException::one('invoice.bad_amount', ['max' => number_format($inv->balance(), 2)]);
            }
            $p = Payment::create(['invoice_id' => $inv->id, 'amount' => $amount, 'paid_at' => $date, 'method' => $method, 'reference' => $reference, 'recorded_by' => $user->id]);
            $paid = round((float) $inv->paid + $amount, 2);
            $inv->update(['paid' => $paid, 'status' => $paid >= (float) $inv->total - 0.01 ? 'paid' : 'partly_paid']);
            $f = $inv->file;
            $this->stages->history($f, $user, 'payment', null, null, $inv->number.' '.number_format($amount, 2).' '.$inv->currency);
            if ($f->status === 'open' && $f->stage === 6 && $f->invoices()->where('status', '!=', 'cancelled')->get()->every(fn ($i) => $i->status === 'paid') && ! $f->check('payment_collected')) {
                $this->stages->tick($f, 'payment_collected', true, $user);
            }
            event(new CrmEvent('payment_recorded', ['number' => $inv->number, 'ref' => $f->ref], route('files.show', $f), [], [$f->sales_id], $user, false));
            return $p;
        });
    }

    public function cancel(Invoice $inv, User $user): void
    {
        abort_unless($user->actsAs('accounts'), 403);
        if ((float) $inv->paid > 0) {
            throw WorkflowException::one('invoice.cannot_cancel_paid');
        }
        $inv->update(['status' => 'cancelled']);
    }
}
