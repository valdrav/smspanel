@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-row">
                <div class="col-md-2 mb-2"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-2 mb-2"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div>
                @if($organizations->isNotEmpty())
                    <div class="col-md-3 mb-2">
                        <select name="organization_id" class="form-control form-control-sm">
                            <option value="">Tüm Organizasyonlar</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" @selected(($filters['organization_id'] ?? '') == $org->id)>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-sm btn-secondary">Filtrele</button></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info"><div class="inner"><h3>{{ $summary['total_count'] }}</h3><p>Toplam SMS</p></div><div class="icon"><i class="fas fa-sms"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success"><div class="inner"><h3>{{ $summary['delivered_count'] }}</h3><p>Teslim Edilen</p></div><div class="icon"><i class="fas fa-check"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger"><div class="inner"><h3>{{ $summary['failed_count'] }}</h3><p>Başarısız</p></div><div class="icon"><i class="fas fa-times"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning"><div class="inner"><h3>{{ number_format($summary['total_segments_used'], 0, ',', '.') }}</h3><p>Kullanılan Segment</p></div><div class="icon"><i class="fas fa-layer-group"></i></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Günlük Gönderim</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Tarih</th><th>Adet</th><th>Segment</th></tr></thead>
                        <tbody>
                            @forelse($summary['by_day'] as $day)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($day['date'])->format('d.m.Y') }}</td>
                                    <td>{{ $day['count'] }}</td>
                                    <td>{{ number_format($day['segments'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">Veri yok</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">En Çok Gönderilen Numaralar</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Numara</th><th>Adet</th></tr></thead>
                        <tbody>
                            @forelse($summary['top_recipients'] as $item)
                                <tr><td>{{ $item['recipient'] }}</td><td>{{ $item['count'] }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">Veri yok</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
