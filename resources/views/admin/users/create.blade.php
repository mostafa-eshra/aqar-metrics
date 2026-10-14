@extends('layouts.admin')

@section('title', __('app.users.create').' | '.__('app.name'))

@section('content')
    <section class="card app-card">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold text-aqar mb-4">{{ __('app.users.create') }}</h1>

            <form method="POST" action="{{ route('admin.users.create') }}">
                @csrf
                @include('admin.users._form', ['user' => null, 'isEdit' => false])
            </form>
        </div>
    </section>
@endsection
