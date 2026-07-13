<div class="row">
    @if(!isset($senderNumber))
        <div class="col-md-6">
            <div class="form-group">
                <label>Kullanıcı <span class="text-danger">*</span></label>
                <select name="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                    <option value="">Seçiniz</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', $selectedUserId ?? '') == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label>Kullanıcı</label>
                <input type="text" class="form-control" value="{{ $senderNumber->user->name }} ({{ $senderNumber->user->email }})" disabled>
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <div class="form-group">
            <label>Gönderici Numarası / Başlık <span class="text-danger">*</span></label>
            <input type="text" name="sender_id" maxlength="11"
                class="form-control @error('sender_id') is-invalid @enderror"
                value="{{ old('sender_id', $senderNumber->sender_id ?? '') }}" required>
            <small class="form-text text-muted">Max 11 karakter, harf ve rakam (örn: DEMO, SMSPANEL)</small>
            @error('sender_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Etiket</label>
            <input type="text" name="label" class="form-control" value="{{ old('label', $senderNumber->label ?? '') }}" placeholder="Örn: Ana hat, Pazarlama">
        </div>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-group">
            <div class="custom-control custom-checkbox mr-3 d-inline-block">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $senderNumber->is_active ?? true))>
                <label class="custom-control-label" for="is_active">Aktif</label>
            </div>
            <div class="custom-control custom-checkbox d-inline-block">
                <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1"
                    @checked(old('is_default', $senderNumber->is_default ?? false))>
                <label class="custom-control-label" for="is_default">Varsayılan</label>
            </div>
        </div>
    </div>
</div>
