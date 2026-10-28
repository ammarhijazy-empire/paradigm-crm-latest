<?php

namespace App\Console\Commands;

use App\Domain\Alerts;
use Illuminate\Console\Command;

class CrmAlerts extends Command
{
    protected $signature = 'crm:alerts {type : followups|leads|invoices|vendors|waiting|performance|weekly|monthly}';

    protected $description = 'Send scheduled CRM alerts and summaries';

    public function handle(Alerts $alerts): int
    {
        $n = match ($this->argument('type')) {
            'followups' => $alerts->followUps(),
            'leads' => $alerts->leadSla(),
            'invoices' => $alerts->invoices(),
            'vendors' => $alerts->vendorDues(),
            'waiting' => $alerts->waitingFiles(),
            'performance' => $alerts->performance(),
            'weekly' => $alerts->summary('weekly'),
            'monthly' => $alerts->summary('monthly'),
            default => -1,
        };
        if ($n < 0) {
            $this->error('Unknown type');
            return self::FAILURE;
        }
        $this->info("{$n} sent");
        return self::SUCCESS;
    }
}
