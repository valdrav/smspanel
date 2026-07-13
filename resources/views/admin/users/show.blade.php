@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <div>
            @can('update', $user)
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Düzenle
                </a>
            @endcan
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Geri
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Kullanıcı Bilgileri</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Ad Soyad</dt>
                        <dd class="col-sm-8">{{ $user->name }}</dd>
                        <dt class="col-sm-4">E-posta</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>
                        <dt class="col-sm-4">Telefon</dt>
                        <dd class="col-sm-8">{{ $user->phone ?? '-' }}</dd>
                        <dt class="col-sm-4">Durum</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>
                        </dd>
                        <dt class="col-sm-4">Roller</dt>
                        <dd class="col-sm-8">
                            @foreach ($user->roles as $role)
                                <span class="badge badge-info">{{ \App\Enums\RoleName::labelFor($role->name) }}</span>
                            @endforeach
                        </dd>
                        <dt class="col-sm-4">Son Giriş</dt>
                        <dd class="col-sm-8">{{ $user->last_login_at?->format('d.m.Y H:i') ?? '-' }}</dd>
                        <dt class="col-sm-4">Kayıt Tarihi</dt>
                        <dd class="col-sm-8">{{ $user->created_at?->format('d.m.Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Gönderici Numaraları</h3>
                    @can('create', App\Models\UserSenderNumber::class)
                        <a href="{{ route('admin.user-sender-numbers.create', ['user_id' => $user->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Numara Ekle
                        </a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Numara</th><th>Etiket</th><th>Varsayılan</th><th>Durum</th></tr></thead>
                        <tbody>
                            @forelse($user->senderNumbers as $sender)
                                <tr>
                                    <td><code>{{ $sender->sender_id }}</code></td>
                                    <td>{{ $sender->label ?? '—' }}</td>
                                    <td>@if($sender->is_default)<span class="badge badge-primary">Evet</span>@else — @endif</td>
                                    <td><span class="badge badge-{{ $sender->is_active ? 'success' : 'secondary' }}">{{ $sender->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">Tanımlı numara yok</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Son Aktiviteler</h3></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($user->activityLogs as $log)
                            <li class="list-group-item">
                                <small class="text-muted">{{ $log->created_at?->format('d.m.Y H:i') }}</small><br>
                                <strong>{{ $log->action->label() }}</strong> — {{ $log->description }}
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aktivite kaydı bulunamadı.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop
