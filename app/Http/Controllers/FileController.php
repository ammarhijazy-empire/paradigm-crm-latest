<?php

namespace App\Http\Controllers;

use App\Domain\FileStageService;
use App\Domain\MarginService;
use App\Domain\NoteService;
use App\Domain\PricingService;
use App\Domain\QuotationService;
use App\Domain\Workflow;
use App\Models\Port;
use App\Models\ShipmentFile;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ShipmentFile::class);
        $q = ShipmentFile::with('client', 'sales')->latest('id');
        $status = $request->input('status', 'open');
        if ($status) {
            $q->where('status', $status);
        }
        if ($s = $request->integer('stage')) {
            $q->where('stage', $s);
        }
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('number', 'like', "%{$term}%")->orWhere('ref', 'like', "%{$term}%")->orWhere('bl_no', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($c) => $c->where('company', 'like', "%{$term}%")));
        }
        if ($request->user()->isManagement() && $request->integer('sales')) {
            $q->where('sales_id', $request->integer('sales'));
        }
        return view('files.index', ['files' => $q->paginate(30)->withQueryString(), 'status' => $status, 'salesUsers' => User::role('sales')->get()]);
    }

    public function operations(Request $request)
    {
        abort_unless($request->user()->actsAs('ops') || $request->user()->actsAs('customs'), 403);
        $files = ShipmentFile::with('client', 'sales')->where('status', 'open')->whereIn('stage', [3, 4, 5])->orderBy('eta')->get()->groupBy('stage');
        return view('files.operations', compact('files'));
    }

    public function show(Request $request, ShipmentFile $file, FileStageService $stages, MarginService $margin, PricingService $pricing, QuotationService $quotes)
    {
        $this->authorize('view', $file);
        $file->load(['client', 'sales', 'ops', 'costLines.vendor', 'quoteLines', 'quoteRounds', 'payTerms', 'documents.uploader', 'docChecks.document', 'booking', 'history.user', 'notes.user', 'updates.user', 'repriceRequests.requester', 'costReviews.reviewer', 'invoices', 'chosenQuote']);
        $u = $request->user();
        $tab = $request->input('tab', 'overview');
        return view('files.show', [
            'file' => $file,
            'tab' => $tab,
            'gates' => $stages->gates($file),
            'canMove' => $stages->canMove($file, $u),
            'stageOwner' => Workflow::owner($file->stage),
            'ranked' => $pricing->ranked($file),
            'costUsd' => $margin->costUsd($file),
            'actualCostUsd' => $margin->costUsd($file, true),
            'sellUsd' => $margin->sellUsd($file),
            'marginUsd' => $margin->marginUsd($file),
            'minMargin' => $margin->minimum(),
            'actualProfit' => $u->can('viewMoney', $file) ? $margin->actualProfitUsd($file) : null,
            'sendProblems' => $quotes->canSend($file),
            'vendors' => Vendor::where('active', true)->orderBy('name')->get(),
            'ports' => Port::pluck('name_'.app()->getLocale(), 'code'),
            'showContact' => $u->can('viewClientContact', $file),
        ]);
    }

    public function note(Request $request, ShipmentFile $file, NoteService $notes)
    {
        $this->authorize('note', $file);
        $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $notes->add($file, $request->user(), $request->input('body'));
        return redirect()->to(route('files.show', $file).'?tab=history#notes');
    }
}
