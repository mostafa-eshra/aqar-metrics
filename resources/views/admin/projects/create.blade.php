@extends('layouts.admin')

@section('title', __('app.projects.create').' | '.__('app.name'))
@section('context', __('app.projects.create'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.projects.title')" :title="__('app.projects.create')" :description="__('app.projects.create_description')" />

    <form id="project-form" method="POST" action="{{ route('admin.projects.create') }}" data-preview-url="{{ route('admin.projects.preview') }}">
        @csrf
        @include('admin.projects._form', ['project' => $project])
    </form>
@endsection

@include('admin.projects._form_script')
