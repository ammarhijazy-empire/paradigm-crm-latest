<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Support\Sectors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $c = $this->route('client');
        return $c ? $this->user()->can('update', $c) : $this->user()->can('create', Client::class);
    }

    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[+\d\s()\-]{6,}$/'],
            'email' => ['required', 'email', 'max:255'],
            'sector' => ['required', Rule::in(Sectors::KEYS)],
            'sector_other' => ['nullable', 'required_if:sector,other', 'string', 'max:120'],
            'products' => ['nullable', 'string', 'max:255'],
            'current_forwarder' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(Client::STATUSES)],
            'next_follow_up' => ['nullable', 'date'],
            'next_order_date' => ['nullable', 'date'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ] + ($this->user()->can('updateCredit', $this->route('client') ?? new Client) ? [
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ] : []);
    }
}
