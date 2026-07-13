@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>{{ $pageTitle }}</h1>
        @if($canManage)
            <a href="{{ route('admin.user-sender-numbers.create', request()->only('user_id')) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Numara
            </a>
        @endif
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-row">
                @if($canManage)
                    <div class="col-md-3 mb-2">
                        <select name="user_id" class="form-control form-control-sm">
                            <option value="">Tüm Kullanıcılar</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Numara veya etiket ara..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="is_active" class="form-control form-control-sm">
                        <option value="">Tüm Durumlar</option>
                        <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Aktif</option>
                        <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        @if($canManage)<th>Kullanıcı</th>@endif
                        <th>Gönderici</th><th>Etiket</th><th>Varsayılan</th><th>Durum</th>
                        @if($canManage)<th>İşlem</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($senderNumbers as $item)
                        <tr>
                            @if($canManage)
                                <td>
                                    <a href="{{ route('admin.users.show', $item->user) }}">{{ $item->user->name }}</a>
                                    <br><small class="text-muted">{{ $item->user->email }}</small>
                                </td>
                            @endif
                            <td><code>{{ $item->sender_id }}</code></td>
                            <td>{{ $item->label ?? '—' }}</td>
                            <td>@if($item->is_default)<span class="badge badge-primary">Varsayılan</span>@else — @endif</td>
                            <td><span class="badge badge-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            @if($canManage)
                                <td>
                                    <a href="{{ route('admin.user-sender-numbers.edit', $item) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.user-sender-numbers.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 6 : 4 }}" class="text-center text-muted py-4">Tanımlı gönderici numarası bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($senderNumbers->hasPages())<div class="card-footer">{{ $senderNumbers->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
