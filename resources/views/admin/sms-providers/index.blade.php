@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>{{ $pageTitle }}</h1>
        @can('create', App\Models\SmsProvider::class)
            <a href="{{ route('admin.sms-providers.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Sağlayıcı</a>
        @endcan
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="alert alert-info">
        <strong>SMS API yapılandırması:</strong> Texcell EIMS kaydını düzenleyip account / password
        ve HTTP base URL bilgisini girin; ardından kaydı <strong>Aktif</strong> ve <strong>Varsayılan</strong>
        yapın. “Bakiye Sorgula” işlemi SMS göndermez.
    </div>
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kod</th><th>Ad</th><th>Sürücü</th><th>Kredi</th><th>Varsayılan</th><th>Durum</th><th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($providers as $provider)
                        <tr>
                            <td><code>{{ $provider->code }}</code></td>
                            <td>{{ $provider->name }}</td>
                            <td>{{ $provider->driverLabel() }}</td>
                            <td>
                                @if($provider->last_balance !== null)
                                    {{ number_format($provider->last_balance, 0, ',', '.') }} adet
                                    <br><small class="text-muted">{{ $provider->last_balance_checked_at?->format('d.m.Y H:i') }}</small>
                                @else — @endif
                            </td>
                            <td>@if($provider->is_default)<span class="badge badge-primary">Varsayılan</span>@else — @endif</td>
                            <td><span class="badge badge-{{ $provider->is_active ? 'success' : 'secondary' }}">{{ $provider->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td>
                                @can('update', $provider)
                                    <a href="{{ route('admin.sms-providers.edit', $provider) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                @endcan
                                <form action="{{ route('admin.sms-providers.test-balance', $provider) }}" method="POST" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-xs btn-info" title="Bağlantıyı ve bakiyeyi test et (SMS göndermez)"><i class="fas fa-sync"></i></button>
                                </form>
                                @can('delete', $provider)
                                    <form action="{{ route('admin.sms-providers.destroy', $provider) }}" method="POST" class="d-inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">@csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Sağlayıcı bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($providers->hasPages())<div class="card-footer">{{ $providers->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
