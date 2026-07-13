@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Ara..." value="{{ $filters['search'] ?? '' }}">
                <select name="action" class="form-control form-control-sm mr-2">
                    <option value="">Tüm Aksiyonlar</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action->value }}" @selected(($filters['action'] ?? '') === $action->value)>{{ $action->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Tarih</th><th>Kullanıcı</th><th>Aksiyon</th><th>Açıklama</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td><span class="badge badge-info">{{ $log->action->label() }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Log kaydı bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())<div class="card-footer">{{ $logs->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
