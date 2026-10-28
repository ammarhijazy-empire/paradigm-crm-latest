<?php

namespace App\Http\Controllers;

use App\Domain\PricingService;
use App\Domain\ShippingService;
use App\Models\FileEvent;
use App\Models\ShipmentFile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    public function booking(Request $request, ShipmentFile $file, ShippingService $shipping)
    {
        $this->authorize('operate', $file);
        $d = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'], 'bl_no' => ['nullable', 'string', 'max:40'], 'etd' => ['nullable', 'date'], 'atd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'], 'acid_no' => ['nullable', 'string', 'max:32', 'regex:/^[0-9A-Za-z-]*$/'], 'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'booking_no' => ['nullable', 'string', 'max:40'], 'vessel_voyage' => ['nullable', 'string', 'max:120'], 'cutoff_at' => ['nullable', 'date'],
            'cfs_warehouse' => ['nullable', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $shipping->saveBooking($file, $d, $request->user());
        return redirect()->to(route('files.show', $file).'?tab=shipping')->with('ok', __('common.saved'));
    }

    public function clientUpdate(Request $request, ShipmentFile $file, ShippingService $shipping)
    {
        $this->authorize('sendClientUpdate', $file);
        $d = $request->validate(['kind' => ['required', Rule::in(['prealert', 'eta_change', 'arrival', 'cleared', 'delivered', 'custom'])], 'body' => ['required', 'string', 'max:5000'], 'lang' => ['required', Rule::in(['ar', 'en'])]]);
        $shipping->sendClientUpdate($file, $request->user(), $d['kind'], $d['body'], $d['lang']);
        return redirect()->to(route('files.show', $file).'?tab=shipping')->with('ok', __('shipping.update_queued'));
    }

    public function actualCosts(Request $request, ShipmentFile $file, PricingService $pricing)
    {
        abort_unless($request->user()->can('operate', $file) || $request->user()->can('bill', $file), 403);
        $d = $request->validate([
            'lines' => ['array', 'max:40'], 'lines.*.id' => ['nullable', 'integer'], 'lines.*.actual' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vendor_due_date' => ['nullable', 'date'], 'lines.*.type' => ['required_without:lines.*.id', 'nullable', Rule::in(['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other'])],
            'lines.*.currency' => ['required_without:lines.*.id', 'nullable', 'string', 'size:3'], 'lines.*.vendor_id' => ['nullable', 'integer', 'exists:vendors,id'], 'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);
        $lines = collect($d['lines'] ?? [])->filter(fn ($l) => ! empty($l['id']) || isset($l['actual']))->all();
        $pricing->saveCostLines($file, $lines, $request->user(), true);
        return redirect()->to(route('files.show', $file).'?tab=money')->with('ok', __('common.saved'));
    }

    public function event(Request $request, ShipmentFile $file)
    {
        $this->authorize('operate', $file);
        $d = $request->validate(['event' => ['required', 'string', 'max:40'], 'location' => ['nullable', 'string', 'max:120'], 'happened_at' => ['required', 'date']]);
        FileEvent::create($d + ['file_id' => $file->id, 'user_id' => $request->user()->id]);
        return redirect()->to(route('files.show', $file).'?tab=shipping');
    }
}
