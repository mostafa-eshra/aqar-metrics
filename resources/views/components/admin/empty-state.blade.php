@props(['title', 'description', 'icon' => 'building'])

<div {{ $attributes->class(['empty-state']) }}>
    <span class="empty-state-icon"><x-admin.icon :name="$icon" size="28" /></span>
    <h2>{{ $title }}</h2>
    <p>{{ $description }}</p>
    @if (isset($action))<div>{{ $action }}</div>@endif
</div>
