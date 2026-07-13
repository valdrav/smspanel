<div class="form-group">
    <label for="name">Paket Adı *</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $package->name ?? '') }}" required>
    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="description">Açıklama</label>
    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $package->description ?? '') }}</textarea>
    @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="sms_amount">SMS Adedi *</label>
        <input type="number" name="sms_amount" id="sms_amount" class="form-control @error('sms_amount') is-invalid @enderror" value="{{ old('sms_amount', $package->sms_amount ?? '') }}" min="1" required>
        @error('sms_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="price">Fiyat (₺)</label>
        <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $package->price ?? '') }}" min="0">
        @error('price')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="sort_order">Sıra</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $package->sort_order ?? 100) }}" min="0">
        @error('sort_order')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
</div>
<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Aktif</label>
    </div>
</div>
<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" value="1" @checked(old('is_public', $package->is_public ?? false))>
        <label class="custom-control-label" for="is_public">Kullanıcılara görünür (satın alınabilir)</label>
    </div>
</div>
