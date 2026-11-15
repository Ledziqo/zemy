@props(['name' => 'overview'])
@php
    $paths = [
        'overview' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'menu' => 'M4 3v6a3 3 0 0 0 6 0V3 M7 3v18 M17 3v18 M17 3c5 3 5 9 0 9',
        'categories' => 'M3 5h7l2 3h9v12H3z',
        'qr' => 'M3 3h6v6H3z M15 3h6v6h-6z M3 15h6v6H3z M15 15h2v2h-2z M21 14v3 M14 21h3 M20 20h1v1h-1',
        'guide' => 'M12 5c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V4c-3-1-6-1-9 1z M12 5v15',
        'orders' => 'M5 3h14v18l-3-2-4 2-4-2-3 2z M9 8h6 M9 12h6',
        'analytics' => 'M4 3v17h17 M8 16v-5 M13 16V7 M18 16V4',
        'people' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        'settings' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z',
        'billing' => 'M3 5h18v14H3z M3 10h18 M7 15h3',
        'database' => 'M21 5c0 2-4 3-9 3S3 7 3 5s4-3 9-3 9 1 9 3z M3 5v14c0 2 4 3 9 3s9-1 9-3V5 M3 12c0 2 4 3 9 3s9-1 9-3',
        'arrow' => 'M5 12h14 M13 6l6 6-6 6',
        'external' => 'M14 3h7v7 M21 3l-9 9 M10 3H3v18h18v-7',
        'logout' => 'M9 3H3v18h6 M9 12h12 M16 7l5 5-5 5',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'ui-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name ?? 'overview'] ?? $paths['overview'] }}"/></svg>
