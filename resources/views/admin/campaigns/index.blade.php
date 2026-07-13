@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Yeni Kampanya</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
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
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Tüm Durumlar</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-sm btn-secondary">Filtrele</button></div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Kampanya</th>@if($canManageAll)<th>Kullanıcı</th>@endif<th>Alıcı</th><th>İlerleme</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->id }}</td>
                            <td>{{ $campaign->name }}</td>
                            @if($canManageAll)<td>{{ $campaign->user->name ?? '—' }}</td>@endif
                            <td>{{ number_format($campaign->total_recipients, 0, ',', '.') }}</td>
                            <td>
                                <div class="progress" style="height: 18px;">
                                    <div class="progress-bar" style="width: {{ $campaign->progressPercent() }}%">{{ $campaign->progressPercent() }}%</div>
                                </div>
                                <small class="text-muted">{{ $campaign->processed_count }}/{{ $campaign->total_recipients }}</small>
                            </td>
                            <td><span class="badge badge-{{ $campaign->status->badgeClass() }}">{{ $campaign->status->label() }}</span></td>
                            <td>{{ $campaign->created_at?->format('d.m.Y H:i') }}</td>
                            <td><a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManageAll ? 8 : 7 }}" class="text-center text-muted py-4">Kampanya bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())<div class="card-footer">{{ $campaigns->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
