@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">Listeye Dön</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span class="badge badge-{{ $campaign->status->badgeClass() }}">{{ $campaign->status->label() }}</span>
                </div>
                <div class="card-body">
                    <p><strong>Mesaj:</strong></p>
                    <div class="border rounded p-3 bg-light">{{ $campaign->message }}</div>
                    <div class="progress mt-3" style="height: 24px;">
                        <div class="progress-bar progress-bar-striped {{ $campaign->status === \App\Enums\CampaignStatus::Processing ? 'progress-bar-animated' : '' }}" style="width: {{ $campaign->progressPercent() }}%">
                            {{ $campaign->progressPercent() }}%
                        </div>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col"><strong>{{ number_format($campaign->total_recipients) }}</strong><br><small>Toplam</small></div>
                        <div class="col"><strong>{{ number_format($campaign->processed_count) }}</strong><br><small>İşlenen</small></div>
                        <div class="col text-success"><strong>{{ number_format($campaign->success_count) }}</strong><br><small>Kuyruğa Alınan</small></div>
                        <div class="col text-danger"><strong>{{ number_format($campaign->failed_count) }}</strong><br><small>Başarısız</small></div>
                    </div>
                </div>
                @if(in_array($campaign->status, [\App\Enums\CampaignStatus::Pending, \App\Enums\CampaignStatus::Processing]))
                    <div class="card-footer">
                        <form action="{{ route('admin.campaigns.cancel', $campaign) }}" method="POST" onsubmit="return confirm('Kampanya iptal edilsin mi?')">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Kampanyayı İptal Et</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="card">
                <div class="card-header">Son Alıcılar</div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Telefon</th><th>Ad</th><th>Durum</th><th>Hata</th></tr></thead>
                        <tbody>
                            @foreach($campaign->recipients as $recipient)
                                <tr>
                                    <td>{{ $recipient->phone }}</td>
                                    <td>{{ $recipient->name ?? '—' }}</td>
                                    <td>{{ $recipient->status->label() }}</td>
                                    <td><small class="text-danger">{{ $recipient->error_message }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Bilgiler</div>
                <div class="card-body">
                    <p><strong>Oluşturulma:</strong> {{ $campaign->created_at?->format('d.m.Y H:i') }}</p>
                    @if($campaign->started_at)<p><strong>Başlangıç:</strong> {{ $campaign->started_at->format('d.m.Y H:i') }}</p>@endif
                    @if($campaign->completed_at)<p><strong>Bitiş:</strong> {{ $campaign->completed_at->format('d.m.Y H:i') }}</p>@endif
                    <p><strong>Parça boyutu:</strong> {{ $campaign->chunk_size }}</p>
                    <p><strong>Parça gecikmesi:</strong> {{ $campaign->chunk_delay_seconds }} sn</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
@if($campaign->status === \App\Enums\CampaignStatus::Processing)
<script>setTimeout(() => location.reload(), 5000);</script>
@endif
@stop
