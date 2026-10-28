<?php

namespace App\Support;

/** Chargeable weight / volume rules from the quote request form. */
class Chargeable
{
    public static function cbm(array $r): float
    {
        return round(((float) ($r['l'] ?? 0)) * ((float) ($r['w'] ?? 0)) * ((float) ($r['h'] ?? 0)) * max(1, (int) ($r['pieces'] ?? 1)) / 1_000_000, 3);
    }

    /** LCL: max(CBM, tonnes) in W/M. */
    public static function lcl(array $r): float
    {
        return round(max(self::cbm($r), ((float) ($r['kg'] ?? 0)) / 1000), 3);
    }

    /** Air: max(kg, L×W×H ÷ 6000). */
    public static function air(array $r): float
    {
        $vol = ((float) ($r['l'] ?? 0)) * ((float) ($r['w'] ?? 0)) * ((float) ($r['h'] ?? 0)) * max(1, (int) ($r['pieces'] ?? 1)) / 6000;
        return round(max((float) ($r['kg'] ?? 0), $vol), 2);
    }

    /** Courier: max(kg, L×W×H ÷ 5000), rounded up to 0.5 kg. */
    public static function courier(array $r): float
    {
        $vol = ((float) ($r['l'] ?? 0)) * ((float) ($r['w'] ?? 0)) * ((float) ($r['h'] ?? 0)) * max(1, (int) ($r['pieces'] ?? 1)) / 5000;
        return ceil(max((float) ($r['kg'] ?? 0), $vol) * 2) / 2;
    }

    public static function for(string $service, array $r): ?float
    {
        return match ($service) {
            'lcl' => self::lcl($r),
            'air' => self::air($r),
            'courier' => self::courier($r),
            default => null,
        };
    }
}
