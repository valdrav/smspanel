@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Yeni Kişi</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.contacts.import') }}" method="POST" enctype="multipart/form-data" class="form-inline">
                @csrf
                <input type="file" name="csv_file" class="form-control-file mr-2" accept=".csv,.txt" required>
                <button type="submit" class="btn btn-sm btn-success mr-2">CSV İçe Aktar</button>
                <a href="{{ route('admin.contacts.export') }}" class="btn btn-sm btn-outline-secondary">CSV Dışa Aktar</a>
            </form>
            <small class="text-muted d-block mt-2">CSV formatı: Ad, Telefon, E-posta, Notlar</small>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-row">
                @if($canManageAll && $users->isNotEmpty())
                    <div class="col-md-3 mb-2">
                        <select name="user_id" class="form-control form-control-sm">
                            <option value="">Tüm Kullanıcılar</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ara..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="is_active" class="form-control form-control-sm">
                        <option value="">Tümü</option>
                        <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Aktif</option>
                        <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-sm btn-secondary">Filtrele</button></div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr><th>#</th>@if($canManageAll)<th>Kullanıcı</th>@endif<th>Ad</th><th>Telefon</th><th>E-posta</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr>
                            <td>{{ $contact->id }}</td>
                            @if($canManageAll)<td>{{ $contact->user->name ?? '—' }}</td>@endif
                            <td>{{ $contact->name ?? '—' }}</td>
                            <td>{{ $contact->phone }}</td>
                            <td>{{ $contact->email ?? '—' }}</td>
                            <td><span class="badge badge-{{ $contact->is_active ? 'success' : 'secondary' }}">{{ $contact->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td>
                                @can('update', $contact)
                                    <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                @endcan
                                @can('delete', $contact)
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManageAll ? 7 : 6 }}" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contacts->hasPages())<div class="card-footer">{{ $contacts->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
