@foreach ($attachments as $attachment)
    <div class="mt-2">
        @if($attachment->isImage())
            <a href="{{ route('admin.support-tickets.attachments.download', $attachment) }}" target="_blank">
                <img src="{{ $attachment->url() }}" alt="{{ $attachment->original_name }}" class="img-thumbnail" style="max-height: 120px;">
            </a>
        @else
            <a href="{{ route('admin.support-tickets.attachments.download', $attachment) }}" class="btn btn-xs btn-outline-secondary">
                <i class="fas fa-paperclip"></i> {{ $attachment->original_name }}
            </a>
        @endif
    </div>
@endforeach
