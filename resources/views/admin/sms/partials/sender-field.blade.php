<div class="form-group">
    <label>Gönderici Başlığı @if($hasAssignedSenders ?? false)<span class="text-danger">*</span>@endif</label>
    @if($hasAssignedSenders ?? false)
        <select name="sender_id" class="form-control sender-id-field" required>
            @foreach($senderNumbers as $sender)
                <option value="{{ $sender->sender_id }}" @selected(old('sender_id', $defaultSenderId) === $sender->sender_id)>
                    {{ $sender->sender_id }}{{ $sender->label ? ' — '.$sender->label : '' }}
                </option>
            @endforeach
        </select>
    @else
        <input type="text" name="sender_id" class="form-control sender-id-field"
            value="{{ old('sender_id', $defaultSenderId) }}" maxlength="11">
    @endif
</div>
