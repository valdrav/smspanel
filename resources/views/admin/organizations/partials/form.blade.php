<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Organizasyon Adı <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $organization->name ?? '') }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Vergi No</label>
            <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $organization->tax_number ?? '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6"><div class="form-group"><label>E-posta</label><input type="email" name="email" class="form-control" value="{{ old('email', $organization->email ?? '') }}"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Telefon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $organization->phone ?? '') }}"></div></div>
</div>
<div class="form-group"><label>Adres</label><textarea name="address" class="form-control" rows="2">{{ old('address', $organization->address ?? '') }}</textarea></div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Durum</label>
            <select name="status" class="form-control" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', isset($organization) ? $organization->status->value : 'active') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4"><div class="form-group"><label>Gönderici Başlığı</label><input type="text" name="sms_sender_id" class="form-control" value="{{ old('sms_sender_id', $organization->sms_sender_id ?? '') }}"></div></div>
    @if(!isset($organization))
        <div class="col-md-4"><div class="form-group"><label>Başlangıç SMS Adedi</label><input type="number" step="1" min="0" name="initial_balance" class="form-control" value="{{ old('initial_balance', 0) }}"></div></div>
    @endif
</div>
<div class="form-group"><label>Notlar</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $organization->notes ?? '') }}</textarea></div>
