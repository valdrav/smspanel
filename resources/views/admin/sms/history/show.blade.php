@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.sms.history.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Mesaj Bilgileri</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Alıcı</dt>
                        <dd class="col-sm-8">{{ $smsMessage->recipient }}</dd>
                        <dt class="col-sm-4">Gönderici</dt>
                        <dd class="col-sm-8">{{ $smsMessage->sender_id ?? '-' }}</dd>
                        <dt class="col-sm-4">Durum</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $smsMessage->status->badgeClass() }}">
                                {{ $smsMessage->status->label() }}
                            </span>
                        </dd>
                        <dt class="col-sm-4">Sağlayıcı</dt>
                        <dd class="col-sm-8">{{ $smsMessage->provider }}</dd>
                        <dt class="col-sm-4">Sağlayıcı Mesaj ID</dt>
                        <dd class="col-sm-8">{{ $smsMessage->provider_message_id ?? '-' }}</dd>
                        <dt class="col-sm-4">Kullanılan Hak</dt>
                        <dd class="col-sm-8">{{ $smsMessage->segments }} segment</dd>
                        <dt class="col-sm-4">Gönderim Zamanı</dt>
                        <dd class="col-sm-8">{{ $smsMessage->sent_at?->format('d.m.Y H:i:s') ?? '-' }}</dd>
                        <dt class="col-sm-4">Teslim Zamanı</dt>
                        <dd class="col-sm-8">{{ $smsMessage->delivered_at?->format('d.m.Y H:i:s') ?? '-' }}</dd>
                        <dt class="col-sm-4">Oluşturulma</dt>
                        <dd class="col-sm-8">{{ $smsMessage->created_at?->format('d.m.Y H:i:s') }}</dd>
                        @if($smsMessage->error_message)
                            <dt class="col-sm-4">Hata</dt>
                            <dd class="col-sm-8 text-danger">{{ $smsMessage->error_message }}</dd>
                        @endif
                        <dt class="col-sm-4">Mesaj</dt>
                        <dd class="col-sm-8">
                            <div class="border rounded p-3 bg-light">{{ $smsMessage->message }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Gönderen</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $smsMessage->user?->name }}</strong></p>
                    <p class="mb-0 text-muted">{{ $smsMessage->user?->email }}</p>
                </div>
            </div>
        </div>
    </div>
@stop
