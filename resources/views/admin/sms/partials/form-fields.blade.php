@if ($mode === 'bulk')
    <div class="form-group">
        <label for="recipients">Telefon Numaraları <span class="text-danger">*</span></label>
        <textarea name="recipients" id="recipients" rows="8"
            class="form-control @error('recipients') is-invalid @enderror"
            placeholder="Her satıra bir numara yazın&#10;5551234567&#10;05551234568&#10;+905551234567">{{ old('recipients') }}</textarea>
        <small class="form-text text-muted">En fazla {{ $maxBatchSize ?? 100 }} numara. Türkçe format: 5XXXXXXXXX</small>
        @error('recipients')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
    </div>
@else
    <div class="form-group">
        <label for="recipient">Telefon Numarası <span class="text-danger">*</span></label>
        <input type="text" name="recipient" id="recipient"
            class="form-control @error('recipient') is-invalid @enderror"
            value="{{ old('recipient') }}" placeholder="5551234567">
        @error('recipient')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
@endif

<div class="form-group">
    <label for="sender_id_{{ $mode }}">Gönderici Başlığı @if($hasAssignedSenders ?? false)<span class="text-danger">*</span>@endif</label>
    @if($hasAssignedSenders ?? false)
        <select name="sender_id" id="sender_id_{{ $mode }}" class="form-control @error('sender_id') is-invalid @enderror" required>
            @foreach($senderNumbers as $sender)
                <option value="{{ $sender->sender_id }}" @selected(old('sender_id', $defaultSenderId) === $sender->sender_id)>
                    {{ $sender->sender_id }}{{ $sender->label ? ' — '.$sender->label : '' }}{{ $sender->is_default ? ' (Varsayılan)' : '' }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">Yalnızca size tanımlı gönderici numaralarını kullanabilirsiniz.</small>
    @else
        <input type="text" name="sender_id" id="sender_id_{{ $mode }}"
            class="form-control @error('sender_id') is-invalid @enderror"
            value="{{ old('sender_id', $defaultSenderId) }}" maxlength="11">
    @endif
    @error('sender_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label for="message_{{ $mode }}">Mesaj <span class="text-danger">*</span></label>
    <textarea name="message" id="message_{{ $mode }}" rows="5" maxlength="918"
        class="form-control @error('message') is-invalid @enderror"
        placeholder="Mesajınızı yazın. Türkçe karakter desteklenir: şğüöçİ">{{ old('message') }}</textarea>
    <small class="form-text text-muted char-counter">0 / 918 karakter</small>
    @error('message')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>
