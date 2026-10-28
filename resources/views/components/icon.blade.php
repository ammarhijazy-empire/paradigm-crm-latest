@props(['name'])
@php
$paths = [
  'home' => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
  'box' => '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
  'plus' => '<path d="M12 5v14M5 12h14"/>',
  'tag' => '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
  'ship' => '<path d="M3 17l2 4h14l2-4H3z"/><path d="M5 17V9h14v8"/><path d="M9 9V5h6v4"/>',
  'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.5 3.3-6 6.5-6s6 2.5 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14c2 .6 3.3 2.8 3.5 6"/>',
  'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5L18 18M6 18l2.5-2.5M15.5 8.5L18 6"/>',
  'truck' => '<path d="M2 6h11v10H2z"/><path d="M13 9h5l3 3v4h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
  'file' => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/><path d="M9 13h7M9 17h7"/>',
  'coins' => '<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v5c0 1.7 2.7 3 6 3s6-1.3 6-3V7"/><path d="M9 15v2c0 1.7 2.7 3 6 3s6-1.3 6-3v-5c0-1.7-2.7-3-6-3"/>',
  'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
  'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/>',
  'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9L7 7M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
  'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/>',
  'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
  'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
  'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>',
  'inbox' => '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
  'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
  'wa' => '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 .8c-1-.4-2-1.4-2.4-2.4l.8-1-1-2z"/>',
  'pdf' => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/><path d="M9 15h6"/>',
];
@endphp
<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
