@extends('layouts.admin')

@section('title', __('app.users.edit').' | '.__('app.name'))
@section('context', __('app.users.edit'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.users.title')" :title="__('app.users.edit')" :description="$user->name.' · '.$user->email" />

    <section class="card app-card user-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.users.edit', $user) }}">
                @csrf
                @include('admin.users._form', ['user' => $user, 'isEdit' => true])
            </form>
        </div>
    </section>
@endsection
