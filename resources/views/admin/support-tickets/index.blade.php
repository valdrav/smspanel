@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        @can('create', App\Models\SupportTicket::class)
            <a href="{{ route('admin.support-tickets.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Talep</a>
        @endcan
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-row">
                @if($canManage && $users->isNotEmpty())
                    <div class="col-md-3 mb-2">
                        <select name="user_id" class="form-control form-control-sm">
                            <option value="">Tüm Kullanıcılar</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Tüm Durumlar</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="priority" class="form-control form-control-sm">
                        <option value="">Tüm Öncelikler</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-sm btn-secondary">Filtrele</button></div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th><th>Konu</th><th>Kategori</th><th>Öncelik</th><th>Durum</th>
                        @if($canManage)<th>Kullanıcı</th><th>Atanan</th>@endif
                        <th>Tarih</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td><code>{{ $ticket->ticket_number }}</code></td>
                            <td>{{ Str::limit($ticket->subject, 40) }}</td>
                            <td>{{ $ticket->category->label() }}</td>
                            <td><span class="badge badge-{{ $ticket->priority->badgeClass() }}">{{ $ticket->priority->label() }}</span></td>
                            <td><span class="badge badge-{{ $ticket->status->badgeClass() }}">{{ $ticket->status->label() }}</span></td>
                            @if($canManage)
                                <td>{{ $ticket->user->name ?? '—' }}</td>
                                <td>{{ $ticket->assignee?->name ?? '—' }}</td>
                            @endif
                            <td>{{ $ticket->created_at?->format('d.m.Y H:i') }}</td>
                            <td><a href="{{ route('admin.support-tickets.show', $ticket) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 9 : 7 }}" class="text-center text-muted py-4">Talep bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())<div class="card-footer">{{ $tickets->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
