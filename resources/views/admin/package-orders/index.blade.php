@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

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
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Tüm Durumlar</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
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
                        <th>#</th><th>Kullanıcı</th><th>Paket</th><th>SMS</th><th>Durum</th><th>Tarih</th>
                        @if($canManage)<th>İşlem</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->user->name ?? '—' }}</td>
                            <td>{{ $order->smsPackage->name ?? '—' }}</td>
                            <td>{{ number_format($order->smsPackage->sms_amount ?? 0, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                            <td>{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            @if($canManage)
                                <td>
                                    @if($order->status->value === 'pending')
                                        <form action="{{ route('admin.package-orders.approve', $order) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="admin_note" value="">
                                            <button type="submit" class="btn btn-xs btn-success" onclick="return confirm('Onaylansın mı? SMS hakkı yüklenecek.')">Onayla</button>
                                        </form>
                                        <form action="{{ route('admin.package-orders.reject', $order) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Reddedilsin mi?')">Reddet</button>
                                        </form>
                                    @else
                                        <small class="text-muted">{{ $order->processor?->name ?? '—' }}</small>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        @if($order->user_note || $order->admin_note)
                            <tr class="bg-light">
                                <td colspan="{{ $canManage ? 7 : 6 }}">
                                    @if($order->user_note)<small><strong>Kullanıcı:</strong> {{ $order->user_note }}</small><br>@endif
                                    @if($order->admin_note)<small><strong>Admin:</strong> {{ $order->admin_note }}</small>@endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="card-footer">{{ $orders->links('pagination::bootstrap-4') }}</div>@endif
    </div>
@stop
