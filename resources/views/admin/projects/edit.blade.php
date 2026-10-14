@extends('layouts.admin')

@section('title', __('app.projects.edit').' | '.__('app.name'))
@section('context', __('app.projects.edit'))

@section('content')
    <x-admin.page-header :eyebrow="__('app.projects.title')" :title="__('app.projects.edit')" :description="$project->name" />

    <form id="project-form" method="POST" action="{{ route('admin.projects.edit', $project) }}" data-preview-url="{{ route('admin.projects.preview') }}">
        @csrf
        @include('admin.projects._form', ['project' => $project])
    </form>
@endsection

@include('admin.projects._form_script')
