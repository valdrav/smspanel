@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <h1>{{ $pageTitle }}</h1>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($stats['balance'], 0, ',', '.') }}</h3>
                    <p>Kalan SMS Hakkı</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['today_count'] }}</h3>
                    <p>Bugün Gönderilen SMS</p>
                </div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['queued_count'] }}</h3>
                    <p>Bekleyen Kuyruk</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($stats['today_segments'], 0, ',', '.') }}</h3>
                    <p>Bugün Kullanılan Segment</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Son SMS Gönderimleri</h3>
                    @can('create', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.send.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-paper-plane"></i> SMS Gönder
                        </a>
                    @endcan
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
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
                                    <td>{{ $message->recipient }}</td>
                                    <td>{{ Str::limit($message->message, 40) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $message->status->badgeClass() }}">
                                            {{ $message->status->label() }}
                                        </span>
                                    </td>
                                    <td>{{ $message->created_at?->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Henüz SMS gönderilmedi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Hızlı Erişim</h3></div>
                <div class="card-body">
                    @can('create', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.send.create') }}" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-paper-plane"></i> SMS Gönder
                        </a>
                    @endcan
                    @can('viewAny', App\Models\SmsMessage::class)
                        <a href="{{ route('admin.sms.history.index') }}" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-history"></i> SMS Geçmişi
                        </a>
                    @endcan
                    @can('viewAny', App\Models\User::class)
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-users"></i> Kullanıcı Yönetimi
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@stop
