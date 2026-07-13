@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">{{ $pageTitle }}</h1>
            <small class="text-muted">Yalnızca süper yönetici paket tanımlayabilir ve yayınlayabilir.</small>
        </div>
        @if($canManage)
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary mt-2 mt-md-0">
                <i class="fas fa-plus"></i> Yeni Paket Tanımla
            </a>
        @endif
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="alert alert-info border-0">
        <strong>Akış:</strong> Süper yönetici paketi oluşturur → katalogda yayınlar → admin / müşteri satın alma talebi gönderir → süper yönetici onaylayınca SMS bakiyesi yüklenir.
    </div>

    @if($canManage)
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="form-row align-items-end">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Görünürlük</label>
                        <select name="is_public" class="form-control form-control-sm">
                            <option value="">Tümü</option>
                            <option value="1" @selected(($filters['is_public'] ?? '') === '1')>Katalogda açık</option>
                            <option value="0" @selected(($filters['is_public'] ?? '') === '0')>Kapalı</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
                        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row">
        @forelse ($packages as $package)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="pkg-admin-card {{ $package->themeClass() }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1">{{ $package->name }}</h5>
                            @if($package->badge)
                                <span class="badge badge-primary">{{ $package->badge }}</span>
                            @endif
                            @if($package->isFeaturedSafe())
                                <span class="badge badge-warning">Öne çıkan</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">
                                {{ $package->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                            <span class="badge badge-{{ $package->is_public ? 'info' : 'secondary' }}">
                                {{ $package->is_public ? 'Yayında' : 'Gizli' }}
                            </span>
                        </div>
                    </div>

                    @if($package->description)
                        <p class="text-muted small mb-2">{{ Str::limit($package->description, 100) }}</p>
                    @endif

                    <div class="pkg-admin-meta mb-3">
                        <div>
                            <strong>{{ number_format($package->sms_amount, 0, ',', '.') }}</strong>
                            <span>SMS</span>
                        </div>
                        <div>
                            <strong>{{ $package->price !== null ? number_format($package->price, 2, ',', '.').' ₺' : '—' }}</strong>
                            <span>Fiyat</span>
                        </div>
                        <div>
                            <strong>#{{ $package->sort_order }}</strong>
                            <span>Sıra</span>
                        </div>
                    </div>

                    @if(count($package->featureList()) > 0)
                        <ul class="pkg-admin-features mb-3">
                            @foreach(array_slice($package->featureList(), 0, 4) as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($canManage)
                        <div class="d-flex">
                            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-warning mr-2">
                                <i class="fas fa-edit"></i> Düzenle
                            </a>
                            <form action="{{ route('admin.packages.destroy', $package) }}" method="POST"
                                onsubmit="return confirm('Paket silinsin mi?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="pkg-empty">
                    <i class="fas fa-layer-group"></i>
                    <h4>Henüz paket yok</h4>
                    <p>İlk paketinizi oluşturup katalogda yayınlayın.</p>
                    @if($canManage)
                        <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">Yeni Paket</a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($packages->hasPages())
        <div class="mt-2">{{ $packages->links('pagination::bootstrap-4') }}</div>
    @endif
@stop
