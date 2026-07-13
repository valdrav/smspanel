@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
@stop

@section('content')
    @include('admin.partials.alerts')

    <div class="pkg-catalog-hero">
        <div>
            <p class="pkg-eyebrow">SMS Kredisi</p>
            <h1>SMS Paketleri</h1>
            <p class="pkg-hero-text">İhtiyacınıza uygun paketi seçin. Talep onaylandıktan sonra SMS haklarınız hesabınıza yüklenir.</p>
        </div>
        <div class="pkg-balance-pill">
            <span>Mevcut bakiye</span>
            <strong>{{ number_format($balance, 0, ',', '.') }} SMS</strong>
        </div>
    </div>

    <div class="row pkg-catalog-grid">
        @forelse ($packages as $package)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="pkg-card {{ $package->themeClass() }} {{ $package->is_featured ? 'is-featured' : '' }}">
                    @if($package->badge || $package->is_featured)
                        <div class="pkg-badge">
                            {{ $package->badge ?: 'Önerilen' }}
                        </div>
                    @endif

                    <div class="pkg-card-head">
                        <h3>{{ $package->name }}</h3>
                        @if($package->description)
                            <p>{{ $package->description }}</p>
                        @endif
                    </div>

                    <div class="pkg-price-block">
                        @if($package->price !== null)
                            <div class="pkg-price">
                                {{ number_format($package->price, 2, ',', '.') }}
                                <small>₺</small>
                            </div>
                        @else
                            <div class="pkg-price pkg-price-ask">Fiyat sorunuz</div>
                        @endif
                        <div class="pkg-sms-amount">
                            <strong>{{ number_format($package->sms_amount, 0, ',', '.') }}</strong> SMS hakkı
                        </div>
                        @if($package->pricePerSms() !== null)
                            <div class="pkg-unit">
                                SMS başı ~ {{ number_format($package->pricePerSms(), 4, ',', '.') }} ₺
                            </div>
                        @endif
                    </div>

                    @if(count($package->featureList()) > 0)
                        <ul class="pkg-features">
                            @foreach($package->featureList() as $feature)
                                <li><i class="fas fa-check"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="pkg-features">
                            <li><i class="fas fa-check"></i> Anında bakiye talebi</li>
                            <li><i class="fas fa-check"></i> Kampanya ve toplu SMS</li>
                            <li><i class="fas fa-check"></i> Kullanım geçmişi ve raporlar</li>
                        </ul>
                    @endif

                    @can('create', App\Models\PackageOrder::class)
                        <form action="{{ route('admin.packages.purchase', $package) }}" method="POST" class="pkg-buy-form mt-auto">
                            @csrf
                            <div class="form-group mb-2">
                                <textarea name="user_note" rows="2" class="form-control form-control-sm"
                                    placeholder="Ödeme / not (opsiyonel)">{{ old('user_note') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-block pkg-buy-btn">
                                <i class="fas fa-shopping-cart mr-1"></i> Satın Alma Talebi
                            </button>
                        </form>
                    @else
                        <div class="alert alert-light border mb-0 mt-auto">Satın alma yetkiniz yok.</div>
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="pkg-empty">
                    <i class="fas fa-box-open"></i>
                    <h4>Henüz yayınlanmış paket yok</h4>
                    <p>Süper yönetici paket tanımlayıp katalogda yayınladığında burada görünür.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($myOrders && $myOrders->isNotEmpty())
        <div class="card dash-panel mt-2">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Taleplerim</h3>
                    <small class="text-muted">Son satın alma talepleriniz</small>
                </div>
                <a href="{{ route('admin.package-orders.index') }}" class="btn btn-sm btn-outline-primary">Tümü</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover dash-table mb-0">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>SMS</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myOrders as $order)
                            <tr>
                                <td><strong>{{ $order->smsPackage->name ?? '—' }}</strong></td>
                                <td>{{ number_format($order->smsPackage->sms_amount ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge badge-{{ $order->status->badgeClass() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@stop
