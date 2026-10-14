@extends('layouts.admin')

@section('title', __('app.users.edit').' | '.__('app.name'))

@section('content')
    <section class="card app-card">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold text-aqar mb-4">{{ __('app.users.edit') }}</h1>

            <form method="POST" action="{{ route('admin.users.edit', $user) }}">
                @csrf
                @include('admin.users._form', ['user' => $user, 'isEdit' => true])
            </form>
        </div>
    </section>
@endsection
