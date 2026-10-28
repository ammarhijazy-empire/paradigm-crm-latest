<?php

namespace App\Http\Controllers;

use App\Domain\PricingService;
use App\Domain\QuotationService;
use App\Models\QuoteRound;
use App\Models\ShipmentFile;
use App\Support\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function __construct(private QuotationService $quotes) {}

    public function saveLines(Request $request, ShipmentFile $file)
    {
        $this->authorize('quote', $file);
        $d = $request->validate([
            'lines' => ['array', 'max:40'], 'lines.*.section' => ['required', Rule::in(['freight', 'origin', 'destination', 'clearance', 'trucking', 'insurance', 'other'])],
            'lines.*.description_en' => ['nullable', 'string', 'max:255'], 'lines.*.description_ar' => ['nullable', 'string', 'max:255'],
            'lines.*.currency' => ['required', 'string', 'size:3'], 'lines.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        $this->quotes->saveLines($file, $d['lines'] ?? [], $request->user());
        return redirect()->to(route('files.show', $file).'?tab=quote')->with('ok', __('common.saved'));
    }

    public function approveMargin(Request $request, ShipmentFile $file)
    {
        $this->authorize('approveMargin', $file);
        $this->quotes->approveMargin($file, $request->user());
        return $this->ok('quote.margin_approved');
    }

    public function pdf(Request $request, ShipmentFile $file)
    {
        $this->authorize('view', $file);
        abort_unless($request->user()->can('quote', $file) || $request->user()->can('viewClientContact', $file), 403);
        $locale = $request->input('lang') === 'ar' ? 'ar' : 'en';
        return Pdf::render('pdf.quotation', ['file' => $file->load('client', 'quoteLines', 'sales', 'chosenQuote')], $locale, "Quotation-{$file->ref}.pdf");
    }

    public function markSent(Request $request, ShipmentFile $file)
    {
        $this->authorize('quote', $file);
        $this->quotes->markSent($file, $request->user(), $request->input('lang') === 'ar' ? 'ar' : 'en');
        return $this->ok('quote.marked_sent');
    }

    public function addRound(Request $request, ShipmentFile $file)
    {
        $this->authorize('quote', $file);
        $d = $request->validate(['counter_offer_usd' => ['nullable', 'numeric', 'min:0'], 'outcome' => ['required', Rule::in(['pending', 'accepted', 'countered', 'rejected'])], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->quotes->addRound($file, $request->user(), $d['counter_offer_usd'] ?? null, $d['outcome'], $d['notes'] ?? null);
        return redirect()->to(route('files.show', $file).'?tab=quote');
    }

    public function updateRound(Request $request, QuoteRound $round)
    {
        $this->authorize('quote', $round->file);
        $d = $request->validate(['counter_offer_usd' => ['nullable', 'numeric', 'min:0'], 'outcome' => ['required', Rule::in(['pending', 'accepted', 'countered', 'rejected'])], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->quotes->updateRound($round, $request->user(), $d['counter_offer_usd'] ?? null, $d['outcome'], $d['notes'] ?? null);
        return redirect()->to(route('files.show', $round->file).'?tab=quote');
    }

    public function reprice(Request $request, ShipmentFile $file, PricingService $pricing)
    {
        $this->authorize('quote', $file);
        $d = $request->validate(['reason' => ['required', 'string', 'max:1000'], 'target_usd' => ['nullable', 'numeric', 'min:0']]);
        $pricing->requestReprice($file, $request->user(), $d['reason'], $d['target_usd'] ?? null);
        return $this->ok('quote.reprice_sent');
    }
}
