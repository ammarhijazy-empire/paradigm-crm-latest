<?php

namespace App\Domain;

use App\Models\ShipmentFile;
use App\Support\Money;
use App\Support\Settings;

class MarginService
{
    public function costUsd(ShipmentFile $f, bool $actual = false): float
    {
        return round($f->costLines->sum(function ($c) use ($actual) {
            $amt = $actual ? ($c->actual ?? $c->estimated) : $c->estimated;
            return Money::toUsd($amt, $c->currency, $actual ? null : (float) $c->exchange_rate);
        }), 2);
    }

    public function sellUsd(ShipmentFile $f): float
    {
        return round($f->quoteLines->sum(fn ($l) => Money::toUsd($l->amount, $l->currency)), 2);
    }

    public function marginUsd(ShipmentFile $f): float
    {
        return round($this->sellUsd($f) - $this->costUsd($f), 2);
    }

    public function minimum(): float
    {
        return (float) Settings::get('min_margin_usd');
    }

    public function belowMinimum(ShipmentFile $f): bool
    {
        return $this->marginUsd($f) < $this->minimum();
    }

    /** Invoiced (before VAT, in USD) minus actual costs including extra fees. */
    public function actualProfitUsd(ShipmentFile $f): ?float
    {
        $inv = $f->invoices()->where('status', '!=', 'cancelled')->get();
        if ($inv->isEmpty()) {
            return null;
        }
        $invoiced = $inv->sum(fn ($i) => Money::toUsd($i->subtotal, $i->currency));
        return round($invoiced - $this->costUsd($f, true), 2);
    }
}
