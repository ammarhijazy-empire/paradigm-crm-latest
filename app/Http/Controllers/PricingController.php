<?php

namespace App\Http\Controllers;

use App\Domain\PricingService;
use App\Models\AiRun;
use App\Models\InboundEmail;
use App\Models\RepriceRequest;
use App\Models\ShipmentFile;
use App\Models\Vendor;
use App\Models\VendorQuote;
use App\Jobs\RunAiFeature;
use App\Ai\AiGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingController extends Controller
{
    public function __construct(private PricingService $pricing) {}

    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($u->actsAs('pricing') || $u->actsAs('pricing_review'), 403);
        $open = ShipmentFile::with('client')->where('status', 'open');
        return view('pricing.index', [
            'toPrice' => (clone $open)->where('pricing_status', 'pending')->orderBy('id')->get(),
            'review' => (clone $open)->where('pricing_status', 'review')->orderBy('id')->get(),
            'reprice' => RepriceRequest::with('file')->where('status', 'open')->latest()->get(),
            'inbox' => InboundEmail::with('vendor', 'file')->latest('received_at')->limit(50)->get(),
            'matchable' => (clone $open)->where('stage', '<=', 2)->orderByDesc('id')->get(['id', 'number', 'ref']),
            'vendors' => Vendor::where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function sendRfq(Request $request, ShipmentFile $file)
    {
        $this->authorize('price', $file);
        $d = $request->validate(['vendor_ids' => ['required', 'array', 'min:1'], 'vendor_ids.*' => ['integer', 'exists:vendors,id'], 'note' => ['nullable', 'string', 'max:2000']]);
        $n = $this->pricing->sendRfq($file, $d['vendor_ids'], $request->user(), $d['note'] ?? null);
        return $this->ok('pricing.rfq_sent', ['n' => $n]);
    }

    private function quoteRules(): array
    {
        $num = ['nullable', 'numeric', 'min:0', 'max:9999999'];
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'], 'carrier' => ['nullable', 'string', 'max:120'], 'routing' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'exists:exchange_rates,currency'], 'freight' => ['required', 'numeric', 'min:0'],
            'surcharges' => ['array', 'max:20'], 'surcharges.*.name' => ['nullable', 'string', 'max:60'], 'surcharges.*.amount' => $num,
            'origin_charges' => $num, 'destination_charges' => $num, 'transit_days' => ['nullable', 'integer', 'min:0', 'max:180'], 'free_days' => ['nullable', 'integer', 'min:0', 'max:180'],
            'valid_until' => ['nullable', 'date'], 'etd' => ['nullable', 'date'], 'conditions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function storeQuote(Request $request, ShipmentFile $file)
    {
        $this->authorize('price', $file);
        $rules = $this->quoteRules();
        $rules['currency'] = ['required', 'string', 'size:3', fn ($a, $v, $f) => $v === 'USD' || \App\Models\ExchangeRate::where('currency', $v)->exists() ?: $f(__('pricing.unknown_currency'))];
        $q = $this->pricing->recordQuote($file, $request->validate($rules), $request->user());
        return redirect()->to(route('files.show', $file).'?tab=pricing')->with('ok', __('pricing.quote_saved', ['ref' => $q->ref]));
    }

    public function confirmQuote(Request $request, VendorQuote $quote)
    {
        $file = $quote->file;
        $this->authorize('price', $file);
        $rules = $this->quoteRules();
        unset($rules['vendor_id']);
        $rules['currency'] = ['required', 'string', 'size:3'];
        $this->pricing->confirmQuote($quote, $request->validate($rules), $request->user());
        return redirect()->to(route('files.show', $file).'?tab=pricing')->with('ok', __('pricing.quote_confirmed', ['ref' => $quote->ref]));
    }

    public function rejectQuote(Request $request, VendorQuote $quote)
    {
        $this->authorize('price', $quote->file);
        abort_if($quote->is_chosen, 422);
        $quote->update(['status' => 'rejected']);
        return redirect()->to(route('files.show', $quote->file).'?tab=pricing');
    }

    public function choose(Request $request, ShipmentFile $file, VendorQuote $quote)
    {
        $this->authorize('price', $file);
        $this->pricing->choose($file, $quote, $request->user());
        return redirect()->to(route('files.show', $file).'?tab=pricing')->with('ok', __('pricing.cost_chosen'));
    }

    public function saveCosts(Request $request, ShipmentFile $file)
    {
        $this->authorize('price', $file);
        $d = $request->validate([
            'lines' => ['array', 'max:40'], 'lines.*.id' => ['nullable', 'integer'],
            'lines.*.type' => ['required', Rule::in(['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other'])],
            'lines.*.description' => ['nullable', 'string', 'max:255'], 'lines.*.vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'lines.*.currency' => ['required', 'string', 'size:3'], 'lines.*.estimated' => ['required', 'numeric', 'min:0'], 'lines.*._delete' => ['nullable', 'boolean'],
        ]);
        $this->pricing->saveCostLines($file, $d['lines'] ?? [], $request->user());
        return redirect()->to(route('files.show', $file).'?tab=pricing')->with('ok', __('common.saved'));
    }

    public function saveMeta(Request $request, ShipmentFile $file)
    {
        $this->authorize('price', $file);
        $d = $request->validate(['quote_etd' => ['nullable', 'date'], 'quote_transit_days' => ['nullable', 'integer', 'min:0', 'max:180'], 'quote_carrier' => ['nullable', 'string', 'max:120']]);
        $this->pricing->saveQuoteMeta($file, $d, $request->user());
        return redirect()->to(route('files.show', $file).'?tab=pricing')->with('ok', __('common.saved'));
    }

    public function submitReview(Request $request, ShipmentFile $file)
    {
        $this->authorize('price', $file);
        $this->pricing->submitForReview($file, $request->user());
        return $this->ok('pricing.sent_review');
    }

    public function review(Request $request, ShipmentFile $file)
    {
        $this->authorize('review', $file);
        $d = $request->validate(['decision' => ['required', Rule::in(['approve', 'return'])], 'reason' => ['nullable', 'string', 'max:1000']]);
        $this->pricing->review($file, $request->user(), $d['decision'] === 'approve', $d['reason'] ?? null);
        return $this->ok($d['decision'] === 'approve' ? 'pricing.reviewed_ok' : 'pricing.returned');
    }

    public function answerReprice(Request $request, RepriceRequest $reprice)
    {
        $this->authorize('price', $reprice->file);
        $d = $request->validate(['answer' => ['required', 'string', 'max:1000'], 'declined' => ['nullable', 'boolean']]);
        $this->pricing->answerReprice($reprice, $request->user(), $d['answer'], (bool) ($d['declined'] ?? false));
        return $this->ok('common.saved');
    }

    public function matchInbound(Request $request, InboundEmail $inbound, AiGuard $guard)
    {
        abort_unless($request->user()->actsAs('pricing'), 403);
        $d = $request->validate(['file_id' => ['required', 'integer', 'exists:files,id'], 'vendor_id' => ['required', 'integer', 'exists:vendors,id']]);
        $inbound->update(['file_id' => $d['file_id'], 'vendor_id' => $d['vendor_id'], 'match_method' => 'manual', 'status' => 'new']);
        if ($guard->enabled('vendor_email')) {
            $run = AiRun::create(['feature' => 'vendor_email', 'user_id' => $request->user()->id, 'file_id' => $d['file_id'], 'status' => 'queued', 'input_ref' => ['inbound_email_id' => $inbound->id], 'subject_type' => $inbound->getMorphClass(), 'subject_id' => $inbound->id]);
            RunAiFeature::dispatch($run->id);
        }
        return $this->ok('pricing.matched');
    }

    public function payTerms(Request $request, ShipmentFile $file)
    {
        $this->authorize('bill', $file);
        $d = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'note' => ['nullable', 'string', 'max:1000']]);
        $this->pricing->decidePayTerms($file, $request->user(), $d['decision'] === 'approve', $d['note'] ?? null);
        return $this->ok('common.saved');
    }
}
