@php
    $canUp = $u->can('uploadDocuments', $file);
    $canResolve = $u->can('resolveDocChecks', $file);
    $lastRun = App\Models\AiRun::where('file_id', $file->id)->where('feature', 'doc_check')->latest()->first();
    $aiOn = (App\Support\Settings::get('ai')['doc_check'] ?? false);
    $checks = $file->docChecks->sortBy(fn ($c) => [$c->status !== 'open', array_search($c->severity, ['blocker', 'warning', 'info'])]);
@endphp
<div class="grid g-main">
    <x-panel :title="__('docs.title')" flush>
        <div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>{{ __('docs.type') }}</th><th>{{ __('docs.name') }}</th><th>{{ __('docs.by') }}</th><th>{{ __('common.date') }}</th></tr></thead><tbody>
        @forelse ($file->documents as $doc)
            <tr><td>{{ __('docs.t_'.$doc->type) }}</td><td class="small"><a href="{{ route('documents.download', $doc) }}" class="mono">{{ Str::limit($doc->original_name, 48) }}</a><div class="small-label">{{ number_format($doc->size / 1024) }} KB</div></td><td><x-avatar :user="$doc->uploader" /></td><td class="num small">{{ d($doc->created_at, true) }}</td></tr>
        @empty <tr><td colspan="4" class="empty">{{ __('common.none') }}</td></tr> @endforelse
        </tbody></table></div>
        @if ($canUp)
            <form method="POST" action="{{ route('documents.store', $file) }}" enctype="multipart/form-data" class="p-3 border-top d-flex gap-2 flex-wrap align-items-end">@csrf
                <div><label class="form-label" for="up-type">{{ __('docs.type') }}</label><select id="up-type" name="type" class="form-select form-select-sm">@foreach (App\Domain\DocumentService::TYPES as $t)@unless(in_array($t, ['whatsapp_chat', 'voice_note']))<option value="{{ $t }}">{{ __('docs.t_'.$t) }}</option>@endunless @endforeach</select></div>
                <div style="min-width:0;flex:1 1 220px"><label class="form-label" for="up-files">{{ __('docs.upload') }}</label><input id="up-files" type="file" name="files[]" multiple class="form-control form-control-sm" required></div>
                <button class="btn btn-sm btn-primary"><x-icon name="upload" /> {{ __('docs.upload') }}</button>
            </form>
        @endif
    </x-panel>
    <x-panel>
        <x-slot:heading>{{ __('docs.check_title') }} <x-pill kind="info">{{ __('ai.suggests') }}</x-pill></x-slot:heading>
        @if ($aiOn && $canResolve && $file->documents->whereIn('type', App\Domain\DocumentService::CHECKABLE)->isNotEmpty())
            <x-slot:actions><form method="POST" action="{{ route('documents.check', $file) }}">@csrf<button class="btn btn-sm btn-light"><x-icon name="spark" /> {{ __('docs.recheck') }}</button></form></x-slot:actions>
        @endif
        @if ($lastRun)
            <div class="small-label mb-2 ai-run-status" @if(in_array($lastRun->status, ['queued', 'running'])) data-poll-reload="{{ route('ai.status', $lastRun) }}" @endif>
                {{ __('docs.last_check', ['when' => d($lastRun->created_at, true)]) }} · {{ __('ai.s_'.$lastRun->status) }}@if($lastRun->status === 'failed') — {{ $lastRun->error }}@endif
            </div>
        @endif
        @forelse ($checks as $c)
            <div class="issue"><span class="sev {{ $c->severity }}"></span><div class="flex-grow-1" style="min-width:0">
                <div class="split"><span><x-pill :kind="['blocker' => 'bad', 'warning' => 'warn', 'info' => 'info'][$c->severity]">{{ __('docs.sev_'.$c->severity) }}</x-pill> <span class="small-label">{{ $c->document ? __('docs.t_'.$c->document->type) : __('docs.file_data') }} · {{ $c->field }}</span></span>
                    @if ($c->status === 'open' && $canResolve && $c->severity !== 'info')
                        <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#dis-{{ $c->id }}" aria-expanded="false">{{ __('docs.dismiss') }}</button>
                    @elseif ($c->status !== 'open')<x-pill kind="ok">{{ __('docs.s_'.$c->status) }}</x-pill>@endif
                </div>
                <div class="mt-1">{{ local_text($c->message) }}</div>
                @if ($c->dismiss_reason)<div class="small-label mt-1">{{ __('docs.dismiss_reason') }}: {{ $c->dismiss_reason }} · {{ $c->resolver?->name }}</div>@endif
                @if ($c->status === 'open' && $canResolve)
                    <form method="POST" action="{{ route('documents.dismiss', $c) }}" class="collapse mt-2" id="dis-{{ $c->id }}">@csrf
                        <textarea name="reason" class="form-control form-control-sm" rows="2" required minlength="5" placeholder="{{ __('docs.dismiss_reason') }}" aria-label="{{ __('docs.dismiss_reason') }}"></textarea>
                        <button class="btn btn-sm btn-primary mt-1">{{ __('common.confirm') }}</button></form>
                @endif
            </div></div>
        @empty <div class="empty">{{ __('common.none') }}</div> @endforelse
        <div class="small-label mt-3">{{ __('docs.blocker_note') }}</div>
    </x-panel>
</div>
