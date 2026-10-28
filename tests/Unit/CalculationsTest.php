<?php

namespace Tests\Unit;

use App\Support\Chargeable;
use App\Support\MoneyWords;
use App\Support\Phone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CalculationsTest extends TestCase
{
    #[Test]
    public function lcl_chargeable_is_the_bigger_of_cbm_and_tonnes(): void
    {
        // 2 pcs × 120×100×110 cm = 2.64 CBM; 1,500 kg = 1.5 t → 2.64
        $this->assertSame(2.64, Chargeable::lcl(['pieces' => 2, 'l' => 120, 'w' => 100, 'h' => 110, 'kg' => 1500]));
        // heavy: 1 CBM vs 3.2 t → 3.2
        $this->assertSame(3.2, Chargeable::lcl(['pieces' => 1, 'l' => 100, 'w' => 100, 'h' => 100, 'kg' => 3200]));
    }

    #[Test]
    public function air_chargeable_uses_6000_divisor(): void
    {
        // 18 × 60×40×50 / 6000 = 360 kg volumetric; actual 410 → 410
        $this->assertSame(410.0, Chargeable::air(['pieces' => 18, 'l' => 60, 'w' => 40, 'h' => 50, 'kg' => 410]));
        // light: 10 × 100×100×100 / 6000 = 1666.67 vs 200 kg
        $this->assertSame(1666.67, Chargeable::air(['pieces' => 10, 'l' => 100, 'w' => 100, 'h' => 100, 'kg' => 200]));
    }

    #[Test]
    public function courier_chargeable_uses_5000_and_rounds_up_to_half_kg(): void
    {
        // 1 × 30×30×30 / 5000 = 5.4 → 5.5
        $this->assertSame(5.5, Chargeable::courier(['pieces' => 1, 'l' => 30, 'w' => 30, 'h' => 30, 'kg' => 2]));
        $this->assertSame(12.0, Chargeable::courier(['pieces' => 1, 'l' => 10, 'w' => 10, 'h' => 10, 'kg' => 11.8]));
    }

    #[Test]
    public function totals_in_words_english(): void
    {
        $this->assertSame('Seventy-seven thousand five hundred and twenty Egyptian pounds only', MoneyWords::en(77520, 'EGP'));
        $this->assertSame('One thousand three hundred and sixty-eight US dollars and fifty cents only', MoneyWords::en(1368.50, 'USD'));
    }

    #[Test]
    public function totals_in_words_arabic(): void
    {
        $this->assertSame('فقط سبعة وسبعون ألف وخمسمائة وعشرون جنيه مصري لا غير', MoneyWords::ar(77520, 'EGP'));
        $this->assertSame('فقط ألفان دولار أمريكي لا غير', MoneyWords::ar(2000, 'USD'));
        $this->assertSame('فقط ثلاثة آلاف ومائتان دولار أمريكي وخمسون سنت لا غير', MoneyWords::ar(3200.50, 'USD'));
        $this->assertSame('فقط مليون يورو لا غير', MoneyWords::ar(1000000, 'EUR'));
    }

    #[Test]
    public function egyptian_phone_numbers_normalise_to_the_same_value(): void
    {
        $this->assertSame('1002345678', Phone::normalize('+20 100 234 5678'));
        $this->assertSame('1002345678', Phone::normalize('01002345678'));
        $this->assertSame('1002345678', Phone::normalize('00201002345678'));
    }
}
