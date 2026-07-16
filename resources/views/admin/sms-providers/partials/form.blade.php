<div class="row">
    @if(!isset($provider))
        <div class="col-md-6">
            <div class="form-group">
                <label>Kod <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <div class="form-group">
            <label>Ad <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $provider->name ?? '') }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Sürücü <span class="text-danger">*</span></label>
            <select name="driver" id="driver-select" class="form-control" required>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->value }}" @selected(old('driver', $provider->driver->value ?? 'mock') === $driver->value)>{{ $driver->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Öncelik</label>
            <input type="number" name="priority" class="form-control" value="{{ old('priority', $provider->priority ?? 100) }}">
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-group">
            <div class="custom-control custom-checkbox mr-3 d-inline-block">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $provider->is_active ?? true))>
                <label class="custom-control-label" for="is_active">Aktif</label>
            </div>
            <div class="custom-control custom-checkbox d-inline-block">
                <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" @checked(old('is_default', $provider->is_default ?? false))>
                <label class="custom-control-label" for="is_default">Varsayılan</label>
            </div>
        </div>
    </div>
</div>

<div id="config-netgsm" class="driver-config border rounded p-3 mb-3">
    <h5>Netgsm Yapılandırması</h5>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Kullanıcı Kodu</label>
                <input type="text" name="config[usercode]" class="form-control" value="{{ old('config.usercode', $provider->config['usercode'] ?? '') }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="config[password]" class="form-control" value="{{ old('config.password', $provider->config['password'] ?? '') }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Gönderici Başlığı</label>
                <input type="text" name="config[msgheader]" class="form-control" value="{{ old('config.msgheader', $provider->config['msgheader'] ?? '') }}">
            </div>
        </div>
    </div>
</div>

<div id="config-iletimerkezi" class="driver-config border rounded p-3 mb-3">
    <h5>İleti Merkezi Yapılandırması</h5>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>API Key</label>
                <input type="text" name="config[api_key]" class="form-control" value="{{ old('config.api_key', $provider->config['api_key'] ?? '') }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Secret / Hash</label>
                <input type="password" name="config[secret]" class="form-control" value="{{ old('config.secret', $provider->config['secret'] ?? '') }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Gönderici</label>
                <input type="text" name="config[sender]" class="form-control" value="{{ old('config.sender', $provider->config['sender'] ?? '') }}">
            </div>
        </div>
    </div>
</div>

<div id="config-easysendsms" class="driver-config border rounded p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">EasySendSMS REST API</h5>
            <small class="text-muted">API anahtarını EasySendSMS panelindeki Account Settings → REST API bölümünden alın.</small>
        </div>
        <a href="https://www.easysendsms.com/rest-api" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-external-link-alt"></i> Dokümantasyon
        </a>
    </div>
    <div class="row">
        <div class="col-md-5">
            <div class="form-group">
                <label>API Key <span class="text-danger">*</span></label>
                <input type="password" name="config[api_key]" class="form-control"
                    value="{{ old('config.api_key', $provider->config['api_key'] ?? '') }}"
                    autocomplete="new-password">
                <small class="text-muted">Şifreli olarak veritabanında saklanır.</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Gönderici Başlığı <span class="text-danger">*</span></label>
                <input type="text" name="config[sender_id]" class="form-control"
                    value="{{ old('config.sender_id', $provider->config['sender_id'] ?? config('sms.easysendsms.sender_id')) }}"
                    maxlength="15" placeholder="SMSPANEL">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>REST API Base URL</label>
                <input type="url" name="config[base_url]" class="form-control"
                    value="{{ old('config.base_url', $provider->config['base_url'] ?? config('sms.easysendsms.base_url')) }}">
            </div>
        </div>
    </div>
    <div class="alert alert-info mb-0 py-2">
        <ul class="mb-0 pl-3">
            <li>Türkiye numaraları API'ye otomatik <strong>90XXXXXXXXXX</strong> olarak gider (<code>+</code> / <code>00</code> kullanılmaz).</li>
            <li>Türkçe/Unicode mesajlarda <code>type=1</code>, düz metinde <code>type=0</code> otomatik seçilir.</li>
            <li>Tek istekte en fazla <strong>30 alıcı</strong>; daha fazlası otomatik parçalanır.</li>
            <li>Alfanumerik gönderici max <strong>11</strong>, sayısal max <strong>15</strong> karakter.</li>
            <li>API anahtarı panelde <em>Account Settings → REST API</em> altındadır. IP kısıtı varsa sunucu IP’nizi whitelist’e ekleyin.</li>
        </ul>
    </div>
</div>

<div id="config-mock" class="driver-config border rounded p-3 mb-3">
    <p class="text-muted mb-0">Mock sağlayıcı ek yapılandırma gerektirmez.</p>
</div>
