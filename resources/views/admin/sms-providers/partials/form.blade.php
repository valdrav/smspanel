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
                    <option value="{{ $driver->value }}" @selected(old('driver', $provider->driverValue() ?? 'texcell') === $driver->value)>{{ $driver->label() }}</option>
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
                <input type="password" name="config[password]" class="form-control" value="{{ old('config.password', isset($provider) && ($provider->driver->value ?? '') === 'netgsm' ? ($provider->config['password'] ?? '') : '') }}">
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
                <input type="text" name="config[api_key]" class="form-control"
                    value="{{ old('config.api_key', isset($provider) && $provider->driver->value === 'iletimerkezi' ? ($provider->config['api_key'] ?? '') : '') }}">
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

<div id="config-texcell" class="driver-config border rounded p-3 mb-3">
    <div class="mb-3">
        <h5 class="mb-1">Texcell EIMS HTTP API</h5>
        <small class="text-muted">EJOIN/Texcell HTTP v3.5 — Charge Rule: Send billing. Kimlik bilgileri şifreli saklanır.</small>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Account <span class="text-danger">*</span></label>
                <input type="text" name="config[account]" class="form-control"
                    value="{{ old('config.account', $provider->config['account'] ?? config('sms.texcell.account')) }}"
                    autocomplete="off">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="config[password]" class="form-control"
                    value="{{ old('config.password', '') }}"
                    placeholder="{{ isset($provider) && !empty($provider->config['password']) ? 'Kayıtlı şifreyi değiştirmek için yeni değer girin' : 'Hesap şifresi' }}"
                    autocomplete="new-password">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Gönderici (sender)</label>
                <input type="text" name="config[sender]" class="form-control"
                    value="{{ old('config.sender', $provider->config['sender'] ?? config('sms.texcell.sender')) }}"
                    maxlength="20" placeholder="Opsiyonel">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Encryption Key</label>
                <input type="text" name="config[encryption_key]" class="form-control"
                    value="{{ old('config.encryption_key', $provider->config['encryption_key'] ?? '') }}"
                    placeholder="Sunucu şifreleme isterse">
                <small class="text-muted">Boş bırakılırsa düz şifre kullanılır.</small>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label>HTTP Base URL <span class="text-danger">*</span></label>
                <input type="url" name="config[base_url]" class="form-control"
                    value="{{ old('config.base_url', $provider->config['base_url'] ?? config('sms.texcell.base_url')) }}"
                    placeholder="http://IP:20003">
            </div>
        </div>
    </div>
    <div class="alert alert-info mb-0 py-2">
        <ul class="mb-0 pl-3">
            <li>Gönderim: <code>POST /sendsms</code> (JSON), bakiye: <code>GET /getbalance</code>, rapor: <code>GET /getreport</code>.</li>
            <li>Türkiye numaraları otomatik <strong>90XXXXXXXXXX</strong> formatına çevrilir.</li>
            <li>Mesaj uzunluğu en fazla <strong>1024</strong> karakter; aynı metin için toplu gönderim tek istekte birleştirilir.</li>
            <li>DLR push URL (Texcell paneline tanımlayın): <code>{{ url('/api/webhooks/texcell') }}/{{ config('sms.texcell.webhook_token') ?: '{TOKEN}' }}/report</code> — HTTP PUT/POST.</li>
            <li>Push yoksa periyodik: <code>php artisan sms:texcell-poll-reports</code></li>
            <li>IP kısıtı varsa uygulama sunucu IP’nizi Texcell whitelist’e ekleyin.</li>
        </ul>
    </div>
</div>

<div id="config-mock" class="driver-config border rounded p-3 mb-3">
    <p class="text-muted mb-0">Mock sağlayıcı ek yapılandırma gerektirmez.</p>
</div>
