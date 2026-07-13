@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.package-orders.index') }}" class="btn btn-outline-secondary btn-sm">Taleplerim</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="row">
        @forelse ($packages as $package)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">{{ $package->name }}</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        @if($package->description)<p class="text-muted">{{ $package->description }}</p>@endif
                        <h3 class="text-primary">{{ number_format($package->sms_amount, 0, ',', '.') }} <small class="text-muted">SMS</small></h3>
                        @if($package->price !== null)
                            <p class="mb-3"><strong>{{ number_format($package->price, 2, ',', '.') }} ₺</strong></p>
                        @endif
                        @can('create', App\Models\PackageOrder::class)
                            <form action="{{ route('admin.packages.purchase', $package) }}" method="POST" class="mt-auto">
                                @csrf
                                <div class="form-group">
                                    <textarea name="user_note" rows="2" class="form-control form-control-sm" placeholder="Notunuz (opsiyonel)">{{ old('user_note') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Satın Alma Talebi Gönder</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Şu an satın alınabilir paket bulunmuyor.</div></div>
        @endforelse
    </div>

    @if($myOrders && $myOrders->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Son Taleplerim</h5></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Paket</th><th>Durum</th><th>Tarih</th></tr></thead>
                    <tbody>
                        @foreach($myOrders as $order)
                            <tr>
                                <td>{{ $order->smsPackage->name }}</td>
                                <td><span class="badge badge-{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                                <td>{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@stop
