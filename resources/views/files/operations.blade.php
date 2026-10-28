@extends('layouts.app')
@section('title', __('nav.ops'))
@section('content')
<x-page-head :title="__('nav.ops')" />
<div class="stack">
@foreach ([3, 4, 5] as $n)
    <x-panel flush>
        <x-slot:heading><x-stage :n="$n" /></x-slot:heading>
        <x-slot:actions><span class="small-label">{{ __('files.owner') }}: {{ __('roles.'.App\Domain\Workflow::owner($n)) }}</span></x-slot:actions>
        @include('partials.file-table', ['files' => $files[$n] ?? collect(), 'days' => true])
    </x-panel>
@endforeach
</div>
@endsection
