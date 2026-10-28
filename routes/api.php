<?php

use App\Http\Controllers\Api\LeadIntakeController;
use Illuminate\Support\Facades\Route;

// Website contact form → new lead for the next salesperson in rotation.
Route::post('/leads', LeadIntakeController::class)->middleware(['throttle:leads', 'lead.key'])->name('api.leads');
