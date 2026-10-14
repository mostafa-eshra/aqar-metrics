@extends('layouts.admin')

@section('title', __('app.users.create').' | '.__('app.name'))
@section('context', __('app.users.create'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.users.title')" :title="__('app.users.create')" :description="__('app.users.create_description')" />

    <section class="card app-card user-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.users.create') }}">
                @csrf
                @include('admin.users._form', ['user' => null, 'isEdit' => false])
            </form>
        </div>
    </section>
@endsection
