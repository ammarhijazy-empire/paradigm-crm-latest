<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Dates
{
    public static function tz(): string
    {
        return config('app.display_timezone', 'Africa/Cairo');
    }

    public static function show(?CarbonInterface $d, bool $time = false): string
    {
        if (! $d) {
            return '—';
        }
        return $d->copy()->setTimezone(self::tz())->format($time ? 'd/m/Y H:i' : 'd/m/Y');
    }

    /** Working days (Sunday–Thursday) between $from and now. */
    public static function workingDaysSince(?CarbonInterface $from): int
    {
        if (! $from) {
            return 0;
        }
        $d = Carbon::parse($from)->setTimezone(self::tz())->startOfDay();
        $end = Carbon::now(self::tz())->startOfDay();
        $n = 0;
        while ($d->lt($end)) {
            $d->addDay();
            if (! in_array($d->dayOfWeek, [CarbonInterface::FRIDAY, CarbonInterface::SATURDAY], true)) {
                $n++;
            }
        }
        return $n;
    }

    public static function today(): Carbon
    {
        return Carbon::now(self::tz())->startOfDay();
    }
}
