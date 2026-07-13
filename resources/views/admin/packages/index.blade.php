@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        @if($canManage)
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Paket</a>
        @endif
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    @if($canManage)
        <div class="card mb-3">
            <div class="card-header">
                <form method="GET" class="form-inline">
                    <select name="is_public" class="form-control form-control-sm mr-2">
                        <option value="">Tüm Görünürlük</option>
                        <option value="1" @selected(($filters['is_public'] ?? '') === '1')>Kullanıcılara Açık</option>
                        <option value="0" @selected(($filters['is_public'] ?? '') === '0')>Kapalı</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
                </form>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th><th>Paket</th><th>SMS Adedi</th><th>Fiyat</th><th>Durum</th><th>Görünür</th><th>Sıra</th>
                        @if($canManage)<th>İşlem</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td>{{ $package->id }}</td>
                            <td>
                                <strong>{{ $package->name }}</strong>
                                @if($package->description)<br><small class="text-muted">{{ Str::limit($package->description, 60) }}</small>@endif
                            </td>
                            <td>{{ number_format($package->sms_amount, 0, ',', '.') }} adet</td>
                            <td>{{ $package->price !== null ? number_format($package->price, 2, ',', '.').' ₺' : '—' }}</td>
                            <td><span class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">{{ $package->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td><span class="badge badge-{{ $package->is_public ? 'info' : 'secondary' }}">{{ $package->is_public ? 'Açık' : 'Kapalı' }}</span></td>
                            <td>{{ $package->sort_order }}</td>
                            @if($canManage)
                                <td>
                                    <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="d-inline" onsubmit="return confirm('Paket silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 8 : 7 }}" class="text-center text-muted py-4">Paket bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($packages->hasPages())<div class="card-footer">{{ $packages->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
