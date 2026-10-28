@if ($invoices->isEmpty())
    <div class="empty">{{ __('common.none') }}</div>
@else
<div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>{{ __('invoice.number') }}</th><th>{{ __('files.client') }}</th><th>{{ __('invoice.total') }}</th><th>{{ __('invoice.balance') }}</th><th>{{ __('invoice.due_date') }}</th><th>{{ __('common.status') }}</th></tr></thead><tbody>
@foreach ($invoices as $i)
    @php $k = ['paid' => 'ok', 'partly_paid' => 'warn', 'cancelled' => '', 'issued' => $i->isOverdue() ? 'bad' : '', 'draft' => ''][$i->status]; @endphp
    <tr><td>@can('view', $i)<a class="row-link mono" href="{{ route('invoices.show', $i) }}">{{ $i->number }}</a>@else<span class="mono">{{ $i->number }}</span>@endcan<div class="small-label mono">{{ $i->file?->number }}</div></td>
        <td class="small">{{ $i->client?->company }}</td><td class="ltr">{{ money($i->total, $i->currency) }}</td><td class="ltr">{{ money($i->balance(), $i->currency) }}</td>
        <td class="num">{{ d($i->due_date) }}</td><td><x-pill :kind="$k">{{ $i->isOverdue() && $i->status !== 'paid' ? __('invoice.overdue') : __('invoice.s_'.$i->status) }}</x-pill></td></tr>
@endforeach
</tbody></table></div>
@endif
