@props(['label', 'value', 'icon' => 'chart', 'tone' => 'primary', 'hint' => null])

<article {{ $attributes->class(['card kpi-card', 'kpi-'.$tone]) }}>
    <div class="card-body">
        <div class="kpi-icon"><x-admin.icon :name="$icon" /></div>
        <div>
            <span class="kpi-label">{{ $label }}</span>
            <strong class="kpi-value" dir="ltr">{{ $value }}</strong>
            @if ($hint)<small>{{ $hint }}</small>@endif
        </div>
    </div>
</article>
