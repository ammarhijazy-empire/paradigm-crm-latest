<?php

namespace App\Events;

use App\Models\ShipmentFile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class FileStageChanged
{
    use Dispatchable;

    public function __construct(public ShipmentFile $file, public ?int $from, public ?int $to, public string $action, public ?User $by) {}
}
