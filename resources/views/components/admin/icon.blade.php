@props(['name', 'size' => 20, 'stroke' => 1.8])

<svg
    {{ $attributes->merge(['class' => 'icon']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $stroke }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('dashboard') <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/> @break
        @case('building') <path d="M3 21h18M6 21V7l6-4v18M18 21V10l-6-3M9 9v.01M9 12v.01M9 15v.01M15 13v.01M15 16v.01"/> @break
        @case('compare') <path d="M5 4v16M19 4v16M9 8h6M12 5l3 3-3 3M15 16H9M12 13l-3 3 3 3"/> @break
        @case('users') <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 12a3 3 0 1 0 0-6M3 21v-2a6 6 0 0 1 12 0v2M16 16a5 5 0 0 1 5 5"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('menu') <path d="M4 7h16M4 12h16M4 17h16"/> @break
        @case('sun') <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/> @break
        @case('moon') <path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8"/> @break
        @case('logout') <path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-6"/> @break
        @case('chevron-left') <path d="m15 18-6-6 6-6"/> @break
        @case('chevron-right') <path d="m9 18 6-6-6-6"/> @break
        @case('filter') <path d="M4 4h16l-6 7v5l-4 2v-7z"/> @break
        @case('search') <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/> @break
        @case('refresh') <path d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7"/> @break
        @case('dots') <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1" fill="currentColor" stroke="none"/> @break
        @case('edit') <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4z"/> @break
        @case('copy') <rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/> @break
        @case('archive') <path d="M3 6h18M5 6v14h14V6M8 3h8l2 3H6zM9 11h6"/> @break
        @case('eye') <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12"/><circle cx="12" cy="12" r="3"/> @break
        @case('chart') <path d="M4 19V9M10 19V5M16 19v-7M22 19V3M2 19h22"/> @break
        @case('download') <path d="M12 3v12M7 10l5 5 5-5M5 21h14"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('alert') <path d="M12 3 2 21h20L12 3zM12 9v4M12 17v.01"/> @break
        @case('lock') <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/> @break
        @case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/> @break
        @case('location') <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="2"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @default <circle cx="12" cy="12" r="9"/> <path d="M12 8v4M12 16v.01"/>
    @endswitch
</svg>
