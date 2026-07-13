@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    @if($contacts->isEmpty())
        <div class="alert alert-warning">Kampanya oluşturmak için önce <a href="{{ route('admin.contacts.index') }}">rehberinize</a> kişi ekleyin.</div>
    @endif
    <div class="card card-primary">
        <form action="{{ route('admin.campaigns.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Kampanya Adı *</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="message">Mesaj *</label>
                    <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                    @error('message')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                @include('admin.sms.partials.sender-field', [
                    'defaultSenderId' => $defaultSenderId,
                    'senderNumbers' => $senderNumbers,
                    'hasAssignedSenders' => $senderNumbers->isNotEmpty(),
                ])
                <div class="form-group">
                    <label for="scheduled_at">Zamanlanmış Gönderim (opsiyonel)</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                        class="form-control @error('scheduled_at') is-invalid @enderror"
                        value="{{ old('scheduled_at') }}">
                    @error('scheduled_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    <small class="text-muted">Boş bırakılırsa kampanya hemen kuyruğa alınır.</small>
                </div>
                <div class="form-group">
                    <label>Alıcılar (boş bırakılırsa tüm aktif rehber)</label>
                    <div class="border rounded p-2" style="max-height: 250px; overflow-y: auto;">
                        @forelse($contacts as $contact)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="contact_{{ $contact->id }}" name="contact_ids[]" value="{{ $contact->id }}" @checked(in_array($contact->id, old('contact_ids', [])))>
                                <label class="custom-control-label" for="contact_{{ $contact->id }}">{{ $contact->name ?? $contact->phone }} — {{ $contact->phone }}</label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Aktif kişi yok.</p>
                        @endforelse
                    </div>
                    <small class="text-muted">En fazla {{ number_format($maxRecipients, 0, ',', '.') }} alıcı. Kuyruk ile parça parça gönderilir.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" @disabled($contacts->isEmpty())>Kampanyayı Başlat</button>
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop
