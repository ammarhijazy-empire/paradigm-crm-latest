<?php

namespace App\Http\Controllers;

use App\Domain\ExchangeRateService;
use App\Models\ExchangeRate;
use App\Models\LeadKey;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    private function admin(Request $r): void
    {
        abort_unless($r->user()->isManagement(), 403);
    }

    public function edit(Request $request)
    {
        $this->admin($request);
        return view('settings.edit', [
            'rates' => ExchangeRate::orderBy('currency')->get(),
            'leadKeys' => LeadKey::latest()->get(),
            'ai' => Settings::get('ai'), 'company' => Settings::get('company'), 'banks' => Settings::get('banks'),
            'minMargin' => Settings::get('min_margin_usd'), 'vat' => Settings::get('vat_rate'), 'sla' => Settings::get('lead_sla_hours'), 'wait' => Settings::get('stage_wait_days'),
            'aiConfigured' => app(\App\Ai\AiClient::class)->configured(),
        ]);
    }

    public function update(Request $request)
    {
        $this->admin($request);
        $d = $request->validate([
            'min_margin_usd' => ['required', 'numeric', 'min:0'], 'vat_rate' => ['required', 'numeric', 'between:0,100'],
            'lead_sla_hours' => ['required', 'integer', 'between:1,72'], 'stage_wait_days' => ['required', 'integer', 'between:1,30'],
            'ai.whatsapp_rfq' => ['nullable', 'boolean'], 'ai.vendor_email' => ['nullable', 'boolean'], 'ai.doc_check' => ['nullable', 'boolean'],
            'ai.monthly_limit_usd' => ['required', 'numeric', 'min:0'],
            'company' => ['array'], 'company.*' => ['nullable', 'string', 'max:255'],
            'banks' => ['array'], 'banks.*.*' => ['nullable', 'string', 'max:120'],
        ]);
        foreach (['min_margin_usd', 'vat_rate', 'lead_sla_hours', 'stage_wait_days'] as $k) {
            Settings::set($k, $d[$k] + 0);
        }
        Settings::set('ai', ['whatsapp_rfq' => $request->boolean('ai.whatsapp_rfq'), 'vendor_email' => $request->boolean('ai.vendor_email'), 'doc_check' => $request->boolean('ai.doc_check'), 'monthly_limit_usd' => (float) $d['ai']['monthly_limit_usd']]);
        Settings::set('company', array_replace(Settings::DEFAULTS['company'], $d['company'] ?? []));
        Settings::set('banks', array_replace_recursive(Settings::DEFAULTS['banks'], $d['banks'] ?? []));
        activity('Settings')->causedBy($request->user())->withProperties(['attributes' => $d])->log('settings_updated');
        return $this->ok('common.saved');
    }

    public function rates(Request $request, ExchangeRateService $svc)
    {
        $this->admin($request);
        $d = $request->validate(['rates' => ['array'], 'rates.*.rate' => ['required', 'numeric', 'gt:0'], 'rates.*.locked' => ['nullable', 'boolean']]);
        foreach ($d['rates'] ?? [] as $cur => $r) {
            abort_unless(preg_match('/^[A-Z]{3}$/', $cur), 422);
            $svc->setManual($cur, (float) $r['rate'], (bool) ($r['locked'] ?? false));
        }
        return $this->ok('common.saved');
    }

    public function refreshRates(Request $request, ExchangeRateService $svc)
    {
        $this->admin($request);
        try {
            $n = $svc->refresh();
        } catch (\Throwable $e) {
            return back()->withErrors(['workflow' => [__('settings.rates_failed', ['error' => Str::limit($e->getMessage(), 120)])]]);
        }
        return $this->ok('settings.rates_updated', ['n' => $n]);
    }

    public function leadKey(Request $request)
    {
        $this->admin($request);
        $d = $request->validate(['name' => ['required', 'string', 'max:80'], 'revoke' => ['nullable', 'integer']]);
        if (! empty($d['revoke'])) {
            LeadKey::whereKey($d['revoke'])->update(['active' => false]);
            return $this->ok('settings.key_revoked');
        }
        $plain = 'lk_'.Str::random(40);
        LeadKey::create(['name' => $d['name'], 'key_hash' => hash('sha256', $plain), 'active' => true]);
        return back()->with('ok', __('settings.key_created'))->with('new_key', $plain);
    }
}
