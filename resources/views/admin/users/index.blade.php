@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Kullanıcı
            </a>
        @endcan
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.users.index') }}" class="form-inline">
                <div class="input-group input-group-sm mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Ara..."
                        value="{{ $filters['search'] ?? '' }}">
                </div>
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">Tüm Durumlar</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <select name="role" class="form-control form-control-sm mr-2">
                    <option value="">Tüm Roller</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>
                            {{ \App\Enums\RoleName::labelFor($role->name) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-secondary mr-1">Filtrele</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Telefon</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <span class="badge badge-info">{{ \App\Enums\RoleName::labelFor($role->name) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge badge-{{ $user->status->badgeClass() }}">
                                    {{ $user->status->label() }}
                                </span>
                            </td>
                            <td>{{ $user->last_login_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td class="text-right">
                                @can('view', $user)
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-xs btn-info" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-xs btn-warning" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer clearfix">
                {{ $users->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
