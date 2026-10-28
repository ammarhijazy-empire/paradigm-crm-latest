@props(['title', 'sub' => null])
<div class="page-head"><div><h1>{{ $title }}</h1>@if($sub)<div class="sub">{{ $sub }}</div>@endif</div>
    @isset($actions)<div class="d-flex gap-2 flex-wrap align-items-center">{{ $actions }}</div>@endisset
</div>
