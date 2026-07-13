@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="alert alert-info">
        Kişisel SMS Hakkınız: <strong>{{ number_format($balance, 0, ',', '.') }} adet</strong>
        @if($availableBalance != $balance)
            <span class="ml-3">Gönderimde kullanılabilir: <strong>{{ number_format($availableBalance, 0, ',', '.') }} adet</strong> (organizasyon bakiyesi)</span>
        @endif
    </div>
    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-row">
                @if($isSuperAdmin && $users->isNotEmpty())
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
                    <select name="type" class="form-control form-control-sm">
                        <option value="">Tüm Tipler</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-2 mb-2"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-sm btn-secondary">Filtrele</button></div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Tarih</th>@if($isSuperAdmin)<th>Kullanıcı</th>@endif<th>Tip</th><th>Adet</th><th>Önce</th><th>Sonra</th><th>Açıklama</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td>{{ $tx->id }}</td>
                            <td>{{ $tx->created_at?->format('d.m.Y H:i') }}</td>
                            @if($isSuperAdmin)<td>{{ $tx->user?->name ?? '—' }}</td>@endif
                            <td><span class="badge badge-{{ $tx->type->badgeClass() }}">{{ $tx->type->label() }}</span></td>
                            <td>{{ number_format($tx->amount, 0, ',', '.') }} adet</td>
                            <td>{{ number_format($tx->balance_before, 0, ',', '.') }}</td>
                            <td>{{ number_format($tx->balance_after, 0, ',', '.') }}</td>
                            <td>{{ $tx->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="text-center text-muted py-4">İşlem bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())<div class="card-footer">{{ $transactions->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
