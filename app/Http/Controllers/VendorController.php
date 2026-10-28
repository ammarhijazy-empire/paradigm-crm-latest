<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::in(['agent', 'line', 'air', 'cfs', 'broker', 'truck', 'courier', 'insurance', 'other'])],
            'country' => ['nullable', 'string', 'size:2'], 'contact_person' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'], 'notes' => ['nullable', 'string', 'max:2000'], 'active' => ['nullable', 'boolean'],
        ];
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Vendor::class);
        $q = Vendor::withCount(['quotes', 'quotes as used_count' => fn ($q) => $q->where('is_chosen', true)])->orderBy('name');
        if ($t = $request->input('type')) {
            $q->where('type', $t);
        }
        return view('vendors.index', ['vendors' => $q->get()]);
    }

    public function create()
    {
        $this->authorize('create', Vendor::class);
        return view('vendors.form', ['vendor' => new Vendor(['active' => true])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Vendor::class);
        $d = $request->validate($this->rules());
        $d['country'] = isset($d['country']) ? strtoupper($d['country']) : null;
        $d['email'] = isset($d['email']) ? mb_strtolower($d['email']) : null;
        Vendor::create($d + ['active' => true]);
        return redirect()->route('vendors.index')->with('ok', __('common.saved'));
    }

    public function edit(Vendor $vendor)
    {
        $this->authorize('update', $vendor);
        return view('vendors.form', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorize('update', $vendor);
        $d = $request->validate($this->rules());
        $d['active'] = $request->boolean('active');
        $d['country'] = isset($d['country']) ? strtoupper($d['country']) : null;
        $vendor->update($d);
        return redirect()->route('vendors.index')->with('ok', __('common.saved'));
    }
}
