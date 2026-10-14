@extends('layouts.admin')

@section('title', __('app.foundation.title').' | '.__('app.name'))

@section('content')
    <section class="card foundation-card overflow-hidden">
        <div class="card-body p-4 p-lg-5 bg-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill bg-aqar-soft text-aqar mb-3 px-3 py-2">
                        {{ __('app.foundation.eyebrow') }}
                    </span>
                    <h1 class="display-5 fw-bold text-aqar mb-3">{{ __('app.foundation.title') }}</h1>
                    <p class="lead text-secondary mb-0">{{ __('app.foundation.description') }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="alert alert-success mb-0" role="status">
                        <div class="fw-bold mb-1">{{ __('app.foundation.status') }}</div>
                        <div class="small">
                            {{ __('app.foundation.calculation_version') }}:
                            <span dir="ltr">{{ config('aqar_metrics.calculation.default_version') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-lg-5 border-top">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border h-100">
                        <div class="text-secondary small mb-1">{{ __('app.foundation.calculation_version') }}</div>
                        <div class="fw-bold" dir="ltr">{{ config('aqar_metrics.calculation.default_version') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border h-100">
                        <div class="text-secondary small mb-1">{{ __('app.foundation.source_version') }}</div>
                        <div class="fw-bold" dir="ltr">{{ config('aqar_metrics.calculation.source_compatibility_version') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-white border h-100">
                        <div class="text-secondary small mb-1">{{ __('app.foundation.input_count') }}</div>
                        <div class="fw-bold">{{ __('app.foundation.input_count_value') }}</div>
                    </div>
                </div>
            </div>

            <h2 class="h4 fw-bold mb-3">{{ __('app.foundation.decisions_title') }}</h2>
            <div class="row g-3">
                @foreach ([
                    ['land_title', 'land_text'],
                    ['commercial_title', 'commercial_text'],
                    ['hotel_title', 'hotel_text'],
                    ['duration_title', 'duration_text'],
                ] as [$title, $text])
                    <div class="col-md-6">
                        <article class="card decision-card bg-white">
                            <div class="card-body">
                                <span class="badge rounded-pill mb-2">{{ __('app.foundation.approved') }}</span>
                                <h3 class="h6 fw-bold">{{ __("app.foundation.{$title}") }}</h3>
                                <p class="text-secondary mb-0">{{ __("app.foundation.{$text}") }}</p>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-4 rounded-3 bg-aqar-soft">
                <h2 class="h5 fw-bold text-aqar">{{ __('app.foundation.environment_title') }}</h2>
                <ul class="mb-0">
                    @foreach (__('app.foundation.environment_items') as $item)
                        <li class="mb-1">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endsection
