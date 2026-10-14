@props(['eyebrow' => null, 'title', 'description' => null])

<header {{ $attributes->class(['page-heading']) }}>
    <div class="page-heading-copy">
        @if ($eyebrow)
            <p class="page-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="page-heading-actions">{{ $actions }}</div>
    @endif
</header>
