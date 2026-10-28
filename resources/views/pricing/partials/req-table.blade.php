@if ($list->isEmpty())
    <div class="empty">{{ __('common.none') }}</div>
@else
<div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>{{ __('files.reference') }}</th><th>{{ __('files.service') }}</th><th>{{ __('files.route') }}</th><th>{{ __('rfq.volume') }}</th><th>{{ __('rfq.price_by') }}</th><th>{{ __('pricing.quotes') }}</th></tr></thead><tbody>
@foreach ($list as $f)
    @php $by = isset($f->inq['price_by']) ? \Illuminate\Support\Carbon::parse($f->inq['price_by']) : null; @endphp
    <tr><td><a class="row-link mono" href="{{ route('files.show', $f) }}?tab=pricing">{{ $f->ref }}</a><div class="small-label mono">{{ $f->number }}</div></td>
        <td>{{ __('services.'.$f->service) }}</td><td class="small route">{{ port_name($f->origin) }} → {{ port_name($f->destination) }}</td>
        <td class="small ltr">@include('partials.volume', ['f' => $f])</td>
        <td class="num">@if($by && $by->lte(today()))<x-pill kind="warn">{{ d($by) }}</x-pill>@else{{ d($by) }}@endif</td>
        <td class="num">{{ $f->vendorQuotes()->count() }}</td></tr>
@endforeach
</tbody></table></div>
@endif
