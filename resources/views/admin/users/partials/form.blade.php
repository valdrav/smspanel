<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user?->name) }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="email">E-posta <span class="text-danger">*</span></label>
            <input type="email" name="email" id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user?->email) }}" required>
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="text" name="phone" id="phone"
                class="form-control @error('phone') is-invalid @enderror"
                value="{{ old('phone', $user?->phone) }}" placeholder="5XXXXXXXXX">
            @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="status">Durum <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                        @selected(old('status', $user?->status?->value ?? 'active') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="password">Şifre @if(!$user)<span class="text-danger">*</span>@endif</label>
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                @if(!$user) required @endif>
            @if($user)<small class="form-text text-muted">Boş bırakılırsa değiştirilmez.</small>@endif
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="password_confirmation">Şifre Tekrar @if(!$user)<span class="text-danger">*</span>@endif</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                @if(!$user) required @endif>
        </div>
    </div>
</div>

<div class="form-group">
    <label>Roller @if(!$user)<span class="text-danger">*</span>@endif</label>
    <div class="row">
        @foreach ($roles as $role)
            <div class="col-md-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="roles[]"
                        id="role_{{ $role->id }}" value="{{ $role->name }}"
                        @checked(in_array($role->name, old('roles', $user?->roles->pluck('name')->toArray() ?? [])))>
                    <label class="custom-control-label" for="role_{{ $role->id }}">{{ \App\Enums\RoleName::labelFor($role->name) }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('roles')<span class="text-danger d-block">{{ $message }}</span>@enderror
    @error('roles.*')<span class="text-danger d-block">{{ $message }}</span>@enderror
</div>
