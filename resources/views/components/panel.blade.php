@props(['title' => null, 'flush' => false, 'id' => null])
<section class="panel" @if($id) id="{{ $id }}" @endif>
    @if ($title || isset($heading) || isset($actions))
        <div class="panel-h"><h2>@isset($heading){{ $heading }}@else{{ $title }}@endisset</h2>@isset($actions)<div class="d-flex gap-2 flex-wrap align-items-center">{{ $actions }}</div>@endisset</div>
    @endif
    <div class="panel-b {{ $flush ? 'flush' : '' }}">{{ $slot }}</div>
</section>
