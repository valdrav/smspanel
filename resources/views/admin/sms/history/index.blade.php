@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <div>
            <a href="{{ route('admin.sms.history.export', request()->query()) }}" class="btn btn-outline-success btn-sm mr-1">
                <i class="fas fa-file-csv"></i> CSV Dışa Aktar
            </a>
            @can('create', App\Models\SmsMessage::class)
                <a href="{{ route('admin.sms.send.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-paper-plane"></i> SMS Gönder
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.sms.history.index') }}" class="form-row">
                <div class="col-md-2 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Numara veya mesaj..."
                        value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Tüm Durumlar</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ $filters['date_to'] ?? '' }}">
                </div>
                @if ($users->isNotEmpty())
                    <div class="col-md-2 mb-2">
                        <select name="user_id" class="form-control form-control-sm">
                            <option value="">Tüm Kullanıcılar</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-sm btn-secondary">Filtrele</button>
                    <a href="{{ route('admin.sms.history.index') }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        @if(\App\Support\UserScope::isPlatformAdmin(auth()->user()))
                            <th>Kullanıcı</th>
                        @endif
                        <th>Alıcı</th>
                        <th>Mesaj</th>
                        <th>Segment</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td>{{ $message->id }}</td>
                            @if(\App\Support\UserScope::isPlatformAdmin(auth()->user()))
                                <td>{{ $message->user?->name ?? '-' }}</td>
                            @endif
                            <td>{{ $message->recipient }}</td>
                            <td>{{ Str::limit($message->message, 30) }}</td>
                            <td>{{ $message->segments }}</td>
                            <td>
                                <span class="badge badge-{{ $message->status->badgeClass() }}">
                                    {{ $message->status->label() }}
                                </span>
                            </td>
                            <td>{{ $message->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="text-right">
                                @can('view', $message)
                                    <a href="{{ route('admin.sms.history.show', $message) }}" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">SMS kaydı bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            <div class="card-footer">{{ $messages->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
@stop
