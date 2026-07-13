@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        @can('create', App\Models\Organization::class)
            <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Organizasyon</a>
        @endcan
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Ara..." value="{{ $filters['search'] ?? '' }}">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">Tüm Durumlar</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th><th>Ad</th><th>E-posta</th><th>SMS Hakkı</th><th>Kullanıcı</th><th>Durum</th><th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        <tr>
                            <td>{{ $organization->id }}</td>
                            <td>{{ $organization->name }}</td>
                            <td>{{ $organization->email ?? '-' }}</td>
                            <td>{{ number_format($organization->sms_balance, 0, ',', '.') }} adet</td>
                            <td>{{ $organization->users_count }}</td>
                            <td><span class="badge badge-{{ $organization->status->badgeClass() }}">{{ $organization->status->label() }}</span></td>
                            <td>
                                <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                                @can('update', $organization)
                                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())<div class="card-footer">{{ $organizations->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
