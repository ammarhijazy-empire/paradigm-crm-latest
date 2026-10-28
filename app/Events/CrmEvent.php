<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Generic business event fired by services (approvals needed, mentions,
 * AI results ready, leads, dues...). NotifyUsers turns it into notifications.
 *
 * @param  string  $type    translation key under notify.* (e.g. "review_needed")
 * @param  array   $params  values for the message
 * @param  array   $roles   roles to notify (management always gets them)
 * @param  array   $userIds specific users to notify as well
 */
class CrmEvent
{
    use Dispatchable;

    public function __construct(
        public string $type,
        public array $params,
        public ?string $url,
        public array $roles = [],
        public array $userIds = [],
        public ?User $by = null,
        public bool $includeManagement = true,
    ) {}
}
