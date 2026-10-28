@php $items = App\Domain\Workflow::items($file->stage); @endphp
<div class="grid g-main">
    <x-panel>
        <x-slot:heading>{{ __('files.checklist') }} · <x-stage :n="$file->stage" /></x-slot:heading>
        <x-slot:actions><span class="small-label">{{ __('files.owner') }}: {{ __('roles.'.$stageOwner) }}</span></x-slot:actions>
        @foreach ($items as $key => [$required, $ownerOverride])
            @php $owner = App\Domain\Workflow::itemOwner($key); $can = $file->status === 'open' && ($u->isManagement() || $u->hasRole($owner)); @endphp
            <form method="POST" action="{{ route('files.tick', $file) }}" class="check-item">
                @csrf
                <input type="hidden" name="item" value="{{ $key }}">
                <input type="hidden" name="value" value="{{ $file->check($key) ? 0 : 1 }}">
                <input class="form-check-input" type="checkbox" id="ck-{{ $key }}" @checked($file->check($key)) @disabled(! $can) data-autosubmit>
                <label for="ck-{{ $key }}"><div>{{ __('checks.'.$key) }}@if($required)<span class="req" title="{{ __('common.required') }}">★</span>@endif</div>
                    <div class="who">{{ __('roles.'.$owner) }}@unless($can) · {{ __('workflow.only_owner_tick', ['role' => __('roles.'.$owner)]) }}@endunless</div></label>
            </form>
        @endforeach
        <div class="mt-3">
            @if ($file->status === 'lost')
                <div class="gate-box bad"><strong>{{ __('files.s_lost') }}</strong> · {{ __('files.stage') }} {{ $file->lost_stage }} · {{ __('lost.'.$file->lost_category) }}<div class="mt-1">{{ $file->lost_reason }}</div></div>
            @elseif ($file->status === 'closed')
                <div class="gate-box ok">{{ __('workflow.closed_on', ['date' => d($file->closed_at)]) }}</div>
            @elseif ($gates)
                <div class="gate-box bad"><strong>{{ __('workflow.blocked') }}</strong><ul>@foreach ($gates as $g)<li>{{ $g }}</li>@endforeach</ul></div>
            @else
                <div class="gate-box ok">{{ __('workflow.ready') }}</div>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
            @if ($file->status === 'open')
                <form method="POST" action="{{ route('files.advance', $file) }}">@csrf
                    <button class="btn btn-primary btn-sm" @disabled(! $canMove)>{{ $file->stage < 7 ? __('workflow.move_to', ['n' => $file->stage + 1]) : __('workflow.close_file') }}</button></form>
                @if ($u->isManagement() && $file->stage > 1)
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#m-back">{{ __('workflow.move_back') }}</button>
                @endif
                @can('markLost', $file)<button class="btn btn-outline-danger btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#m-lost">{{ __('workflow.mark_lost') }}</button>@endcan
            @elseif ($u->isManagement())
                <form method="POST" action="{{ route('files.reopen', $file) }}">@csrf<button class="btn btn-light btn-sm">{{ __('workflow.reopen') }}</button></form>
            @endif
        </div>
        @if ($file->status === 'open' && ! $canMove)<div class="small-label mt-2">{{ __('workflow.not_owner', ['role' => __('roles.'.$stageOwner)]) }}</div>@endif
    </x-panel>
    <div class="stack">
        <x-panel :title="__('files.client')">
            <div class="fw-semibold mb-2">@can('view', $file->client)<a class="row-link" href="{{ route('clients.show', $file->client) }}">{{ $file->client->company }}</a>@else{{ $file->client->company }}@endcan</div>
            @if ($showContact)
                <dl class="dl"><dt>{{ __('clients.contact_person') }}</dt><dd>{{ $file->client->contact_person }}</dd><dt>{{ __('clients.phone') }}</dt><dd class="ltr">{{ $file->client->phone }}</dd><dt>{{ __('clients.email') }}</dt><dd class="ltr">{{ $file->client->email }}</dd></dl>
            @else
                <div class="locked">{{ __('files.contact_hidden') }}</div>
            @endif
        </x-panel>
        <x-panel :title="__('files.shipment')">
            <dl class="dl">
                <dt>{{ __('files.service') }}</dt><dd>{{ __('services.'.$file->service) }}@if($file->addons) + {{ collect($file->addons)->map(fn ($a) => __('services.'.$a))->join('، ') }}@endif</dd>
                <dt>{{ __('rfq.volume') }}</dt><dd class="ltr">@include('partials.volume', ['f' => $file])</dd>
                <dt>{{ __('rfq.commodity') }}</dt><dd>{{ $file->inq['commodity'] ?? '—' }}</dd>
                <dt>{{ __('files.carrier') }}</dt><dd>{{ $file->carrier ?: '—' }}</dd>
                <dt>{{ __('files.bl') }}</dt><dd class="mono">{{ $file->bl_no ?: '—' }}</dd>
                <dt>{{ __('files.etd') }}</dt><dd class="num">{{ d($file->atd ?? $file->etd) }}</dd>
                <dt>{{ __('files.eta') }}</dt><dd class="num">{{ d($file->eta) }}</dd>
                <dt>{{ __('files.acid') }}</dt><dd class="mono">{{ $file->acid_no ?: '—' }}</dd>
                <dt>{{ __('files.sales') }}</dt><dd><x-avatar :user="$file->sales" /> {{ $file->sales->name }}</dd>
                <dt>{{ __('files.ops') }}</dt><dd>@if($file->ops)<x-avatar :user="$file->ops" /> {{ $file->ops->name }}@else — @endif</dd>
            </dl>
        </x-panel>
    </div>
</div>
@push('modals')
<div class="modal fade" id="m-lost" tabindex="-1" aria-labelledby="m-lost-h" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('files.lost', $file) }}">@csrf
    <div class="modal-header"><h5 class="modal-title" id="m-lost-h">{{ __('workflow.lost_title') }}</h5></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label" for="lost-cat">{{ __('workflow.lost_cat') }}</label><select id="lost-cat" name="category" class="form-select">@foreach (App\Domain\FileStageService::LOST_CATEGORIES as $c)<option value="{{ $c }}">{{ __('lost.'.$c) }}</option>@endforeach</select></div>
        <div><label class="form-label" for="lost-reason">{{ __('workflow.lost_reason') }}</label><textarea id="lost-reason" name="reason" class="form-control" rows="3" required></textarea></div>
        <div class="small-label mt-2">{{ __('files.stage') }}: {{ $file->stage }} · {{ __('stages.'.$file->stage) }}</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-danger">{{ __('workflow.mark_lost') }}</button></div>
</form></div></div>
<div class="modal fade" id="m-back" tabindex="-1" aria-labelledby="m-back-h" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('files.back', $file) }}">@csrf
    <div class="modal-header"><h5 class="modal-title" id="m-back-h">{{ __('workflow.move_back') }}</h5></div>
    <div class="modal-body"><label class="form-label" for="back-reason">{{ __('workflow.back_reason') }}</label><textarea id="back-reason" name="reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ __('common.confirm') }}</button></div>
</form></div></div>
@endpush
