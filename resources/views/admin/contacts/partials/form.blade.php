<div class="form-group">
    <label for="name">Ad</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $contact->name ?? '') }}">
    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="phone">Telefon *</label>
    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $contact->phone ?? '') }}" required>
    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="email">E-posta</label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $contact->email ?? '') }}">
    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="notes">Notlar</label>
    <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $contact->notes ?? '') }}</textarea>
    @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group mb-0">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $contact->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Aktif</label>
    </div>
</div>
