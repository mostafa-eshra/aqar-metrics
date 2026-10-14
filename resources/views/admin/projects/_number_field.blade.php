<div class="{{ $columnClass ?? 'col-md-6' }}">
    <label class="form-label" for="{{ $name }}">{{ __('app.projects.attributes.'.$name) }}</label>
    <div class="input-group" dir="ltr">
        <input
            class="form-control text-end @error($name) is-invalid @enderror"
            id="{{ $name }}"
            name="{{ $name }}"
            type="text"
            inputmode="decimal"
            value="{{ old($name, $project->getAttribute($name)) }}"
            autocomplete="off"
        >
        <span class="input-group-text">{{ __('app.projects.units.'.$unit) }}</span>
    </div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
