<?php

namespace App\Http\Controllers\Api;

use App\Domain\LeadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadIntakeController extends Controller
{
    public function __invoke(Request $request, LeadService $leads)
    {
        $d = $request->validate([
            'company' => ['required', 'string', 'max:255'], 'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'required_without:email'], 'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'message' => ['nullable', 'string', 'max:5000'], 'source' => ['nullable', 'string', 'max:40'],
        ]);
        $lead = $leads->receive($d);
        return response()->json(['ok' => true, 'id' => $lead->id], 201);
    }
}
