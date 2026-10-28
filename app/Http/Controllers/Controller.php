<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function ok(string $key, array $params = [])
    {
        return back()->with('ok', __($key, $params));
    }
}
