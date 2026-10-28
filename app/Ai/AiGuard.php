<?php

namespace App\Ai;

use App\Models\AiRun;
use App\Support\Settings;

class AiGuard
{
    public const FEATURES = ['whatsapp_rfq', 'vendor_email', 'doc_check'];

    public function enabled(string $feature): bool
    {
        return (bool) (Settings::get('ai')[$feature] ?? false);
    }

    public function spentThisMonth(): float
    {
        return (float) AiRun::where('created_at', '>=', now()->startOfMonth())->sum('cost_usd');
    }

    public function limit(): float
    {
        return (float) (Settings::get('ai')['monthly_limit_usd'] ?? 0);
    }

    /** Throws when the feature is off or the monthly cost limit is reached. */
    public function check(string $feature): void
    {
        if (! $this->enabled($feature)) {
            throw new AiException(__('ai.feature_off'));
        }
        if ($this->limit() > 0 && $this->spentThisMonth() >= $this->limit()) {
            throw new AiException(__('ai.limit_reached'));
        }
    }
}
