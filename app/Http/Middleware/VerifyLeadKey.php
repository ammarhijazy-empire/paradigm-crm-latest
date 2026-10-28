<?php

namespace App\Http\Middleware;

use App\Models\LeadKey;
use Closure;
use Illuminate\Http\Request;

/** The website lead form sends a secret key header; only its SHA-256 is stored. */
class VerifyLeadKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) $request->header(config('crm.lead_api_key_header'));
        $row = $key !== '' ? LeadKey::where('key_hash', hash('sha256', $key))->where('active', true)->first() : null;
        if (! $row) {
            return response()->json(['message' => 'Invalid key'], 401);
        }
        $row->forceFill(['last_used_at' => now()])->save();
        return $next($request);
    }
}
