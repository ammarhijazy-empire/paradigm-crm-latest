<?php

namespace App\Support;

/** Total in words for invoices, English and Arabic, any currency. */
class MoneyWords
{
    private const CUR = [
        'USD' => [['US dollars', 'cents'], ['دولار أمريكي', 'سنت']],
        'EUR' => [['euros', 'cents'], ['يورو', 'سنت']],
        'EGP' => [['Egyptian pounds', 'piastres'], ['جنيه مصري', 'قرش']],
        'CNY' => [['Chinese yuan', 'fen'], ['يوان صيني', 'فين']],
        'AED' => [['UAE dirhams', 'fils'], ['درهم إماراتي', 'فلس']],
        'TRY' => [['Turkish lira', 'kuruş'], ['ليرة تركية', 'قرش']],
        'GBP' => [['pounds sterling', 'pence'], ['جنيه إسترليني', 'بنس']],
        'SAR' => [['Saudi riyals', 'halalas'], ['ريال سعودي', 'هللة']],
    ];

    public static function en(float $amount, string $currency): string
    {
        [$whole, $frac] = self::split($amount);
        $w = self::CUR[$currency][0] ?? [$currency, 'cents'];
        $s = ucfirst(self::enNumber($whole)).' '.$w[0];
        if ($frac) {
            $s .= ' and '.self::enNumber($frac).' '.$w[1];
        }
        return $s.' only';
    }

    public static function ar(float $amount, string $currency): string
    {
        [$whole, $frac] = self::split($amount);
        $w = self::CUR[$currency][1] ?? [$currency, 'سنت'];
        $s = 'فقط '.self::arNumber($whole).' '.$w[0];
        if ($frac) {
            $s .= ' و'.self::arNumber($frac).' '.$w[1];
        }
        return $s.' لا غير';
    }

    private static function split(float $amount): array
    {
        $cents = (int) round($amount * 100);
        return [intdiv($cents, 100), $cents % 100];
    }

    public static function enNumber(int $n): string
    {
        $a = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $h = function (int $x) use ($a, $b): string {
            $s = '';
            if ($x >= 100) {
                $s .= $a[intdiv($x, 100)].' hundred';
                $x %= 100;
                if ($x) {
                    $s .= ' and ';
                }
            }
            if ($x >= 20) {
                $s .= $b[intdiv($x, 10)].($x % 10 ? '-'.$a[$x % 10] : '');
            } elseif ($x) {
                $s .= $a[$x];
            }
            return $s;
        };
        if ($n === 0) {
            return 'zero';
        }
        $parts = [];
        foreach ([[1_000_000_000, 'billion'], [1_000_000, 'million'], [1_000, 'thousand']] as [$v, $w]) {
            if ($n >= $v) {
                $parts[] = $h(intdiv($n, $v)).' '.$w;
                $n %= $v;
            }
        }
        if ($n) {
            $parts[] = $h($n);
        }
        return implode(' ', $parts);
    }

    public static function arNumber(int $n): string
    {
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hund = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        $b100 = fn (int $x) => $x < 10 ? $ones[$x] : ($x < 20 ? $teens[$x - 10] : ($x % 10 ? $ones[$x % 10].' و'.$tens[intdiv($x, 10)] : $tens[intdiv($x, 10)]));
        $b1000 = function (int $x) use ($hund, $b100) {
            $p = array_filter([$hund[intdiv($x, 100)], $x % 100 ? $b100($x % 100) : '']);
            return implode(' و', $p);
        };
        $scale = fn (int $x, string $one, string $two, string $few, string $many) => $x === 1 ? $one : ($x === 2 ? $two : ($x <= 10 ? $b1000($x).' '.$few : $b1000($x).' '.$many));
        if ($n === 0) {
            return 'صفر';
        }
        $parts = [];
        $bil = intdiv($n, 1_000_000_000);
        $mil = intdiv($n % 1_000_000_000, 1_000_000);
        $th = intdiv($n % 1_000_000, 1000);
        $rest = $n % 1000;
        if ($bil) {
            $parts[] = $scale($bil, 'مليار', 'ملياران', 'مليارات', 'مليار');
        }
        if ($mil) {
            $parts[] = $scale($mil, 'مليون', 'مليونان', 'ملايين', 'مليون');
        }
        if ($th) {
            $parts[] = $scale($th, 'ألف', 'ألفان', 'آلاف', 'ألف');
        }
        if ($rest) {
            $parts[] = $b1000($rest);
        }
        return implode(' و', $parts);
    }
}
