@php
    $featureText = old('features');
    if ($featureText === null && isset($package)) {
        $featureText = implode("\n", $package->featureList());
    }
@endphp

<div class="form-group">
    <label for="name">Paket Adı *</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $package->name ?? '') }}" required placeholder="Örn: Profesyonel Paket">
    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label for="description">Kısa Açıklama</label>
    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror"
        placeholder="Paket hakkında kısa satış metni...">{{ old('description', $package->description ?? '') }}</textarea>
    @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="badge">Rozet Metni</label>
        <input type="text" name="badge" id="badge" class="form-control @error('badge') is-invalid @enderror"
            value="{{ old('badge', $package->badge ?? '') }}" placeholder="Örn: En Popüler, Kurumsal, Yeni">
        @error('badge')<span class="invalid-feedback">{{ $message }}</span>@enderror
        <small class="text-muted">Kartın üstünde küçük etiket olarak görünür.</small>
    </div>
    <div class="form-group col-md-6">
        <label for="theme">Kart Teması *</label>
        <select name="theme" id="theme" class="form-control @error('theme') is-invalid @enderror" required>
            @foreach ([
                'indigo' => 'Indigo (varsayılan)',
                'emerald' => 'Yeşil',
                'cyan' => 'Turkuaz',
                'amber' => 'Kehribar',
                'rose' => 'Gül',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(old('theme', $package->theme ?? 'indigo') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('theme')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
</div>

<div class="form-group">
    <label for="features">Özellik Listesi</label>
    <textarea name="features" id="features" rows="6" class="form-control @error('features') is-invalid @enderror"
        placeholder="Her satıra bir özellik yazın&#10;Anında bakiye yükleme&#10;7/24 destek&#10;Kampanya gönderimi">{{ $featureText }}</textarea>
    @error('features')<span class="invalid-feedback">{{ $message }}</span>@enderror
    <small class="text-muted">Her satır katalog kartında madde olarak listelenir.</small>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label for="sms_amount">SMS Adedi *</label>
        <input type="number" name="sms_amount" id="sms_amount" class="form-control @error('sms_amount') is-invalid @enderror"
            value="{{ old('sms_amount', $package->sms_amount ?? '') }}" min="1" required>
        @error('sms_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="price">Paket Fiyatı (₺)</label>
        <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror"
            value="{{ old('price', $package->price ?? '') }}" min="0" placeholder="0.00">
        @error('price')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="sort_order">Sıralama</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
            value="{{ old('sort_order', $package->sort_order ?? 100) }}" min="0">
        @error('sort_order')<span class="invalid-feedback">{{ $message }}</span>@enderror
        <small class="text-muted">Küçük sayı önce gösterilir.</small>
    </div>
</div>

<div class="pkg-form-flags">
    <div class="custom-control custom-checkbox mb-2">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Aktif</label>
    </div>
    <div class="custom-control custom-checkbox mb-2">
        <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" value="1" @checked(old('is_public', $package->is_public ?? false))>
        <label class="custom-control-label" for="is_public">Katalogda görünür (satın alınabilir)</label>
    </div>
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured ?? false))>
        <label class="custom-control-label" for="is_featured">Öne çıkarılmış paket (tavsiye edilen)</label>
    </div>
</div>
