@props(['kind' => ''])
<span {{ $attributes->merge(['class' => 'pill '.$kind]) }}>{{ $slot }}</span>
