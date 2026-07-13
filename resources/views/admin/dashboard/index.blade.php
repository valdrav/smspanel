@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="dash-hero">
        <div class="dash-hero-text">
            <p class="dash-eyebrow">SMS Panel</p>
            <h1>{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p class="dash-subtitle">Bugünkü gönderimler, bakiye ve son aktivitelerin özeti.</p>
        </div>
        <div class="dash-hero-actions">
            @can('create', App\Models\SmsMessage::class)
                <a href="{{ route('admin.sms.send.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane mr-1"></i> SMS Gönder
                </a>
            @endcan
            @can('viewAny', App\Models\SmsMessage::class)
                <a href="{{ route('admin.sms.history.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-history mr-1"></i> Geçmiş
                </a>
            @endcan
        </div>
    </div>

    <div class="row dash-stats">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dash-stat-card dash-stat-balance">
                <div class="dash-stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="dash-stat-body">
                    <span class="dash-stat-label">Kalan SMS Hakkı</span>
                    <strong class="dash-stat-value">{{ number_format($stats['balance'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dash-stat-card dash-stat-today">
                <div class="dash-stat-icon"><i class="fas fa-paper-plane"></i></div>
                <div class="dash-stat-body">
                    <span class="dash-stat-label">Bugün Gönderilen</span>
                    <strong class="dash-stat-value">{{ number_format($stats['today_count'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dash-stat-card dash-stat-queue">
                <div class="dash-stat-icon"><i class="fas fa-clock"></i></div>
                <div class="dash-stat-body">
                    <span class="dash-stat-label">Bekleyen Kuyruk</span>
                    <strong class="dash-stat-value">{{ number_format($stats['queued_count'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dash-stat-card dash-stat-segments">
                <div class="dash-stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="dash-stat-body">
                    <span class="dash-stat-label">Bugün Kullanılan Segment</span>
                    <strong class="dash-stat-value">{{ number_format($stats['today_segments'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card dash-panel h-100">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Son SMS Gönderimleri</h3>
                        <small class="text-muted">En son kayıtlar</small>
                    </div>
                    @can('viewAny', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.history.index') }}" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
                    @endcan
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover dash-table mb-0">
                        <thead>
                            <tr>
                                <th>Alıcı</th>
                                <th>Mesaj</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentMessages as $message)
                                <tr>
                                    <td>
                                        <span class="dash-phone">{{ $message->recipient }}</span>
                                    </td>
                                    <td class="dash-message">{{ Str::limit($message->message, 48) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $message->status->badgeClass() }} dash-badge">
                                            {{ $message->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-muted text-nowrap">{{ $message->created_at?->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                        Henüz SMS gönderilmedi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card dash-panel h-100">
                <div class="card-header border-0">
                    <h3 class="card-title mb-0">Hızlı Erişim</h3>
                    <small class="text-muted">Sık kullanılan işlemler</small>
                </div>
                <div class="card-body dash-quick-links">
                    @can('create', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.send.create') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-primary"><i class="fas fa-paper-plane"></i></span>
                            <span>
                                <strong>SMS Gönder</strong>
                                <small>Tekil veya toplu gönderim</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                    @can('contacts.view')
                        <a href="{{ route('admin.contacts.index') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-info"><i class="fas fa-address-book"></i></span>
                            <span>
                                <strong>Rehber</strong>
                                <small>Kişileri yönet</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                    @can('campaigns.view')
                        <a href="{{ route('admin.campaigns.index') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-warning"><i class="fas fa-bullhorn"></i></span>
                            <span>
                                <strong>Kampanyalar</strong>
                                <small>Toplu kampanya takibi</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.history.index') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-secondary"><i class="fas fa-history"></i></span>
                            <span>
                                <strong>SMS Geçmişi</strong>
                                <small>Gönderim kayıtları</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                    @can('packages.view')
                        <a href="{{ route('admin.packages.catalog') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-success"><i class="fas fa-box"></i></span>
                            <span>
                                <strong>SMS Paketleri</strong>
                                <small>Bakiye yükleme talebi</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\User::class)
                        <a href="{{ route('admin.users.index') }}" class="dash-quick-link">
                            <span class="dash-quick-icon bg-dark"><i class="fas fa-users"></i></span>
                            <span>
                                <strong>Kullanıcı Yönetimi</strong>
                                <small>Hesap ve roller</small>
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@stop
