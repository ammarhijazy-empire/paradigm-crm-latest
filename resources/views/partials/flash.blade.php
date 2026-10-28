@if (session('ok'))
    <div class="alert-flash ok" role="status">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="alert-flash bad" role="alert">
        @if ($errors->has('workflow'))<strong>{{ __('workflow.blocked') }}</strong>@endif
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
