@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">{{ __('app.users.name') }}</label>
        <input
            class="form-control @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $user?->name) }}"
            required
            maxlength="255"
            autocomplete="name"
        >
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">{{ __('app.users.email') }}</label>
        <input
            class="form-control @error('email') is-invalid @enderror"
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $user?->email) }}"
            required
            maxlength="255"
            autocomplete="email"
            dir="ltr"
        >
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password">{{ __('app.users.password') }}</label>
        <input
            class="form-control @error('password') is-invalid @enderror"
            id="password"
            name="password"
            type="password"
            @required(! $isEdit)
            maxlength="255"
            autocomplete="new-password"
            dir="ltr"
        >
        @if ($isEdit)
            <div class="form-text">{{ __('app.users.password_edit_help') }}</div>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">{{ __('app.users.password_confirmation') }}</label>
        <input
            class="form-control"
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            @required(! $isEdit)
            maxlength="255"
            autocomplete="new-password"
            dir="ltr"
        >
    </div>

    <div class="col-md-6">
        <label class="form-label" for="role">{{ __('app.users.role') }}</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            @foreach (\App\Enums\UserRole::cases() as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value ?? \App\Enums\UserRole::USER->value) === $role->value)>
                    {{ $role->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="is_active" value="0">
            <input
                class="form-check-input"
                id="is_active"
                name="is_active"
                type="checkbox"
                value="1"
                @checked((bool) old('is_active', $user?->is_active ?? true))
            >
            <label class="form-check-label" for="is_active">{{ __('app.users.is_active') }}</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-aqar" type="submit">{{ __('app.save') }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">{{ __('app.cancel') }}</a>
</div>
