<div class="grid g-2">
    <x-panel :title="__('files.t_history')">
        @forelse ($file->history as $h)
            <div class="tl-item"><span class="tl-dot"></span><div style="min-width:0">
                <div>{{ __('history.'.$h->action, ['from' => $h->from_stage, 'to' => $h->to_stage]) }}@if($h->action === 'ticked' || $h->action === 'unticked'): {{ __('checks.'.$h->note) }}@elseif($h->note) — {{ $h->note }}@endif</div>
                <div class="small-label">{{ d($h->created_at, true) }} · {{ $h->user?->name ?? __('common.system') }}</div></div></div>
        @empty <div class="empty">{{ __('common.none') }}</div> @endforelse
    </x-panel>
    <x-panel :title="__('common.notes')" id="notes">
        @foreach ($file->notes as $n)
            <div class="tl-item"><x-avatar :user="$n->user" /><div style="min-width:0"><div class="text-pre">{!! preg_replace('/@([\p{L}\p{N}_.-]+)/u', '<span class="mention">@$1</span>', e($n->body)) !!}</div><div class="small-label">{{ d($n->created_at, true) }}</div></div></div>
        @endforeach
        @can('note', $file)
            <form method="POST" action="{{ route('files.note', $file) }}" class="mt-3">@csrf
                <label class="form-label" for="note-body">{{ __('files.note_hint') }}</label><textarea id="note-body" name="body" class="form-control" rows="2" required></textarea>
                <button class="btn btn-sm btn-primary mt-2">{{ __('common.add') }}</button></form>
        @endcan
    </x-panel>
</div>
