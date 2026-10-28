<?php

namespace App\Http\Requests;

use App\Domain\DocumentService;
use App\Domain\InquiryValidator;
use App\Models\ShipmentFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Shape and type checks. Completeness is InquiryValidator's job. */
class RfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ShipmentFile::class);
    }

    public function rules(): array
    {
        $num = ['nullable', 'numeric', 'min:0', 'max:9999999'];
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'direction' => ['required', Rule::in(['import', 'export'])],
            'service' => ['nullable', Rule::in(ShipmentFile::MAIN_SERVICES)],
            'addons' => ['array'], 'addons.*' => [Rule::in(ShipmentFile::ADDONS)],
            'origin' => ['nullable', 'string', 'max:12', 'exists:ports,code'],
            'destination' => ['nullable', 'string', 'max:12', 'exists:ports,code'],
            'incoterm' => ['nullable', Rule::in(InquiryValidator::INCOTERMS_SEA)],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'inq' => ['array'],
            'inq.rows' => ['array', 'max:30'],
            'inq.rows.*.type' => ['nullable', 'string', 'max:12'], 'inq.rows.*.truck' => ['nullable', Rule::in(['FTL', 'LTL'])],
            'inq.rows.*.count' => $num, 'inq.rows.*.pieces' => $num, 'inq.rows.*.l' => $num, 'inq.rows.*.w' => $num, 'inq.rows.*.h' => $num,
            'inq.rows.*.kg' => $num, 'inq.rows.*.tonnes' => $num,
            'inq.cargo' => ['required', Rule::in(['general', 'hazardous', 'reefer', 'fragile', 'liquid', 'oog'])],
            'inq.commodity' => ['nullable', 'string', 'max:255'],
            'inq.un' => ['nullable', 'string', 'max:10'], 'inq.imo' => ['nullable', 'string', 'max:10'], 'inq.pg' => ['nullable', Rule::in(['I', 'II', 'III'])],
            'inq.temp' => ['nullable', 'numeric', 'between:-60,40'],
            'inq.oog_l' => $num, 'inq.oog_w' => $num, 'inq.oog_h' => $num,
            'inq.ready' => ['nullable', 'date'], 'inq.price_by' => ['nullable', 'date'],
            'inq.free_days' => ['nullable', 'integer', 'min:0', 'max:180'],
            'inq.freight_terms' => ['nullable', Rule::in(['prepaid', 'collect'])],
            'inq.pay' => ['nullable', Rule::in(['cash_in_advance', 'against_documents', 'on_arrival', 'credit'])],
            'inq.credit_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'inq.notes' => ['nullable', 'string', 'max:2000'],
            'msds' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.DocumentService::MAX_KB],
            'attachments' => ['array', 'max:10'], 'attachments.*' => ['file', 'mimes:'.DocumentService::ALLOWED_MIMES, 'max:'.DocumentService::MAX_KB],
            'ai_run_id' => ['nullable', 'integer'],
            'ai_checked' => ['nullable', 'boolean'],
            'action' => ['required', Rule::in(['draft', 'send'])],
        ];
    }

    /** Clean payload for InquiryValidator / RfqService (no unknown keys, numbers as numbers). */
    public function payload(): array
    {
        $v = $this->validated();
        $inq = $v['inq'] ?? [];
        $inq['rows'] = array_values(array_map(fn ($r) => array_map(fn ($x) => is_numeric($x) ? $x + 0 : $x, array_filter($r, fn ($x) => $x !== null && $x !== '')), $inq['rows'] ?? []));
        if (! empty($this->input('inq.msds_doc'))) {
            $inq['msds'] = (int) $this->input('inq.msds_doc');
        }
        return [
            'client_id' => (int) $v['client_id'], 'direction' => $v['direction'], 'service' => $v['service'] ?? null,
            'addons' => array_values($v['addons'] ?? []), 'origin' => $v['origin'] ?? null, 'destination' => $v['destination'] ?? null,
            'incoterm' => $v['incoterm'] ?? null, 'pickup_address' => $v['pickup_address'] ?? null, 'delivery_address' => $v['delivery_address'] ?? null,
            'inq' => array_filter($inq, fn ($x) => $x !== null && $x !== ''),
        ];
    }
}
