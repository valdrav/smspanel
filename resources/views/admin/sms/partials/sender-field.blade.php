@php
    $texcellDefault = ($defaultProviderIsTexcell ?? false)
        || (config('sms.default_provider') === 'texcell');
@endphp
<div class="form-group">
    <label>Gönderici Başlığı
        @if(($hasAssignedSenders ?? false) && ! $texcellDefault)
            <span class="text-danger">*</span>
        @endif
    </label>
    @if($hasAssignedSenders ?? false)
        <select name="sender_id" class="form-control sender-id-field" @if(! $texcellDefault) required @endif>
            @if($texcellDefault)
                <option value="">— Texcell varsayılan (gönderici yok) —</option>
            @endif
            @foreach($senderNumbers as $sender)
                <option value="{{ $sender->sender_id }}" @selected(old('sender_id', $texcellDefault ? '' : $defaultSenderId) === $sender->sender_id)>
                    {{ $sender->sender_id }}{{ $sender->label ? ' — '.$sender->label : '' }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">
            @if($texcellDefault)
                Texcell API’de sender opsiyoneldir; boş bırakılırsa hesap rotası kullanılır.
            @else
                Yalnızca size tanımlı gönderici numaralarını kullanabilirsiniz.
            @endif
        </small>
    @else
        <input type="text" name="sender_id" class="form-control sender-id-field"
            value="{{ old('sender_id', $texcellDefault ? '' : $defaultSenderId) }}" maxlength="20"
            placeholder="{{ $texcellDefault ? 'Opsiyonel — boş bırakılabilir' : '' }}">
        @if($texcellDefault)
            <small class="form-text text-muted">Texcell PDF: sender zorunlu değil. Boş bırakın; SMSPANEL göndermeyin.</small>
        @endif
    @endif
</div>
