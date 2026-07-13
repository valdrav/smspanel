<div class="form-group">
    <label for="name">Şablon Adı *</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $template->name ?? '') }}" required>
    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="body">Mesaj Metni *</label>
    <textarea name="body" id="body" rows="5" maxlength="918" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', $template->body ?? '') }}</textarea>
    @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group mb-0">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $template->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Aktif</label>
    </div>
</div>
