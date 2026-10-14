@if ($value['is_available'])
    <span class="result-number" dir="ltr">{{ $value['formatted_value'] }}</span>
    @if (($showUnit ?? true) && $value['unit_label'] !== __('app.projects.results.units.none'))
        <span class="text-secondary small">{{ $value['unit_label'] }}</span>
    @endif
@else
    <span class="text-danger fw-semibold">{{ __('app.projects.results.unavailable') }}</span>
    @foreach ($value['reasons'] as $reason)
        <small class="d-block text-danger mt-1">{{ $reason }}</small>
    @endforeach
@endif
