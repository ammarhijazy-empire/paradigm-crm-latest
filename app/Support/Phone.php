<?php

namespace App\Support;

class Phone
{
    /** "+20 100 234 5678", "00201002345678", "01002345678" all become "1002345678". */
    public static function normalize(?string $phone): string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        $d = preg_replace('/^(0020|20)(?=1\d{9}$)/', '', $d);
        return ltrim($d, '0');
    }
}
