<?php

namespace App\Console\Commands;

use App\Domain\ExchangeRateService;
use Illuminate\Console\Command;

class RefreshRates extends Command
{
    protected $signature = 'crm:rates';

    protected $description = 'Update exchange rates per USD (locked currencies are skipped)';

    public function handle(ExchangeRateService $svc): int
    {
        $this->info($svc->refresh().' rates updated');
        return self::SUCCESS;
    }
}
