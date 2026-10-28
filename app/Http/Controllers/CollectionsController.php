<?php

namespace App\Http\Controllers;

use App\Models\CostLine;
use App\Models\Invoice;
use Illuminate\Http\Request;

class CollectionsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->actsAs('accounts'), 403);
        return view('invoices.collections', [
            'invoices' => Invoice::with('client', 'file')->whereIn('status', ['issued', 'partly_paid'])->orderBy('due_date')->get(),
            'dues' => CostLine::with('vendor', 'file')->whereNotNull('vendor_due_date')->whereNull('vendor_paid_at')->orderBy('vendor_due_date')->get(),
        ]);
    }

    public function vendorPaid(Request $request, CostLine $line)
    {
        abort_unless($request->user()->actsAs('accounts'), 403);
        $line->update(['vendor_paid' => $line->actual ?? $line->estimated, 'vendor_paid_at' => now()]);
        $f = $line->file;
        if ($f->status === 'open' && $f->stage === 6 && $f->costLines()->whereNotNull('vendor_due_date')->whereNull('vendor_paid_at')->doesntExist() && ! $f->check('suppliers_paid')) {
            app(\App\Domain\FileStageService::class)->tick($f, 'suppliers_paid', true, $request->user());
        }
        return $this->ok('invoice.vendor_paid');
    }
}
