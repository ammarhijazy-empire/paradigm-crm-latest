<?php

namespace App\Domain;

use App\Models\RefCounter;
use App\Models\User;
use App\Support\Dates;
use Illuminate\Support\Facades\DB;

/**
 * Running numbers that two users must never share. Each counter row is
 * locked for update inside a transaction.
 */
class RefGenerator
{
    public static function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $row = RefCounter::where('key', $key)->lockForUpdate()->first();
            if (! $row) {
                RefCounter::insertOrIgnore(['key' => $key, 'value' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $row = RefCounter::where('key', $key)->lockForUpdate()->first();
            }
            $row->value = $row->value + 1;
            $row->save();
            return $row->value;
        });
    }

    /** PC-26-047 */
    public static function fileNumber(): string
    {
        $yy = Dates::today()->format('y');
        return sprintf('PC-%s-%03d', $yy, self::next("file:{$yy}"));
    }

    /** AH-2610-6 (sales initials + YYMM + running number for that salesperson and month) */
    public static function salesRef(User $sales): string
    {
        $ym = Dates::today()->format('ym');
        $ini = $sales->displayInitials();
        return sprintf('%s-%s-%d', $ini, $ym, self::next("ref:{$ini}:{$ym}"));
    }

    /** AH-2610-6-Q3 */
    public static function quoteRef(string $fileRef): string
    {
        return $fileRef.'-Q'.self::next("quote:{$fileRef}");
    }

    /** INV-26-0188 */
    public static function invoiceNumber(): string
    {
        $yy = Dates::today()->format('y');
        return sprintf('INV-%s-%04d', $yy, self::next("invoice:{$yy}"));
    }
}
