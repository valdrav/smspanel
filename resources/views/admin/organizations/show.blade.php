@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Geri</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $organization->name }}</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">SMS Hakkı</dt><dd class="col-sm-8"><strong>{{ number_format($organization->sms_balance, 0, ',', '.') }} adet</strong></dd>
                        <dt class="col-sm-4">E-posta</dt><dd class="col-sm-8">{{ $organization->email ?? '-' }}</dd>
                        <dt class="col-sm-4">Telefon</dt><dd class="col-sm-8">{{ $organization->phone ?? '-' }}</dd>
                        <dt class="col-sm-4">Durum</dt><dd class="col-sm-8"><span class="badge badge-{{ $organization->status->badgeClass() }}">{{ $organization->status->label() }}</span></dd>
                        <dt class="col-sm-4">Gönderici</dt><dd class="col-sm-8">{{ $organization->sms_sender_id ?? '-' }}</dd>
                        <dt class="col-sm-4">Adres</dt><dd class="col-sm-8">{{ $organization->address ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Kullanıcılar ({{ $organization->users->count() }})</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($organization->users as $user)
                        <li class="list-group-item">{{ $user->name }} — {{ $user->email }}</li>
                    @empty
                        <li class="list-group-item text-muted">Kullanıcı yok</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            @can('credit', $organization)
                <div class="card card-success">
                    <div class="card-header"><h3 class="card-title">SMS Kredisi Yükle</h3></div>
                    <form action="{{ route('admin.organizations.credit', $organization) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>SMS Adedi</label>
                                <input type="number" step="1" min="1" name="amount" class="form-control @error('amount') is-invalid @enderror" required>
                                @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label>Açıklama</label>
                                <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="SMS kredisi yükleme" required>
                                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="card-footer"><button type="submit" class="btn btn-success btn-block">Yükle</button></div>
                    </form>
                </div>
            @endcan
            <div class="card">
                <div class="card-header"><h3 class="card-title">Son İşlemler</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($organization->walletTransactions as $tx)
                        <li class="list-group-item">
                            <small>{{ $tx->created_at?->format('d.m.Y H:i') }}</small><br>
                            <span class="badge badge-{{ $tx->type->badgeClass() }}">{{ $tx->type->label() }}</span>
                            {{ number_format($tx->amount, 0, ',', '.') }} adet — {{ $tx->description }}
                        </li>
                    @empty
                        <li class="list-group-item text-muted">İşlem yok</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@stop
