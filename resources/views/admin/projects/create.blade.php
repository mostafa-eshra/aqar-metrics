@extends('layouts.admin')

@section('title', __('app.projects.create').' | '.__('app.name'))

@section('content')
    <section class="card app-card">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold text-aqar mb-4">{{ __('app.projects.create') }}</h1>

            <form id="project-form" method="POST" action="{{ route('admin.projects.create') }}" data-preview-url="{{ route('admin.projects.preview') }}">
                @csrf
                @include('admin.projects._form', ['project' => $project])
            </form>
        </div>
    </section>
@endsection

@include('admin.projects._form_script')
