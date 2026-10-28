<?php

namespace App\Policies;

use App\Models\AiRun;
use App\Models\User;

class AiRunPolicy
{
    public function view(User $u, AiRun $r): bool
    {
        return $r->user_id === $u->id;
    }

    public function viewAny(User $u): bool
    {
        return false;
    }
}
