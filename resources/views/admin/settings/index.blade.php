@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')

    @if(session('api_token_plain'))
        <div class="alert alert-warning">
            <strong>Yeni API Token (bir kez gösterilir):</strong><br>
            <code class="d-block mt-2 p-2 bg-dark text-white rounded">{{ session('api_token_plain') }}</code>
            <small>Header: <code>Authorization: Bearer TOKEN</code></small>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Genel Ayarlar</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Uygulama Adı</label>
                                    <input type="text" name="app_name" class="form-control" value="{{ old('app_name', $values['app_name']) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Slogan</label>
                                    <input type="text" name="app_tagline" class="form-control" value="{{ old('app_tagline', $values['app_tagline']) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Destek E-posta</label>
                            <input type="email" name="support_email" class="form-control" value="{{ old('support_email', $values['support_email']) }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ana Renk</label>
                                    <input type="color" name="primary_color" class="form-control settings-color-input" value="{{ old('primary_color', $values['primary_color']) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vurgu Rengi</label>
                                    <input type="color" name="accent_color" class="form-control settings-color-input" value="{{ old('accent_color', $values['accent_color']) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Logo</label>
                            @if($values['logo_path'])
                                <div class="mb-2"><img src="{{ asset('storage/'.$values['logo_path']) }}" height="48" alt="Logo"></div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo" value="1">
                                    <label class="custom-control-label" for="remove_logo">Logoyu kaldır</label>
                                </div>
                            @endif
                            <input type="file" name="logo" class="form-control-file" accept="image/*">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                    </div>
                </div>
            </form>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">REST API</h3></div>
                <div class="card-body">
                    <p class="text-muted">API endpoint: <code>{{ url('/api/v1') }}</code></p>
                    <ul class="small">
                        <li><code>GET /api/v1/balance</code> — SMS hakkı</li>
                        <li><code>POST /api/v1/sms/send</code> — SMS gönder</li>
                    </ul>
                    <form action="{{ route('admin.settings.api-token') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Yeni token oluşturulsun mu? Eski token geçersiz olur.')">
                            <i class="fas fa-key"></i> API Token Oluştur / Yenile
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="settings-preview-box">
                @if($values['logo_path'])
                    <img src="{{ asset('storage/'.$values['logo_path']) }}" height="60" class="mb-3" alt="">
                @endif
                <h3>{{ $values['app_name'] }}</h3>
                <p class="mb-0 opacity-75">{{ $values['app_tagline'] ?: 'Slogan önizlemesi' }}</p>
            </div>
        </div>
    </div>
@stop
