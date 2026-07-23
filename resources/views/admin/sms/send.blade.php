@extends('adminlte::page')

@section('title', $pageTitle)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">{{ $pageTitle }}</h1>
            @if($appTagline ?? false)<small class="text-muted">{{ $appTagline }}</small>@endif
        </div>
        <div class="sms-stat-pill mt-2 mt-md-0">
            <i class="fas fa-bolt text-warning"></i>
            {{ $balanceLabel ?? 'Kalan hak' }}:
            <strong id="sms-balance-value">
                @if(($balanceUnit ?? 'SMS') === 'USD')
                    {{ number_format($balance, 4, ',', '.') }}
                @else
                    {{ number_format($balance, 0, ',', '.') }}
                @endif
            </strong>
            <span id="sms-balance-unit">{{ $balanceUnit ?? 'SMS' }}</span>
        </div>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')

    @if(!empty($isPlatformOperator) && !empty($texcellSyncError))
        <div class="alert alert-danger">
            <strong>Sağlayıcı bakiyesi alınamadı.</strong> {{ $texcellSyncError }}
            <br><small>Whitelist’e <em>sunucu public IP</em> eklenmeli (ev/PC IP’si değil). Teşhis: <code>php artisan sms:texcell-diagnose</code></small>
        </div>
    @endif

    <div class="sms-workspace">
        <div class="row">
            {{-- Sol: Form --}}
            <div class="col-lg-7 mb-4">
                <div class="sms-compose-card">
                    @if($templates->isNotEmpty())
                        <div class="p-3 border-bottom">
                            <label class="mb-1 small text-muted">Şablon Seç</label>
                            <select id="template-select" class="form-control form-control-sm">
                                <option value="">— Şablon seçin —</option>
                                @foreach($templates as $template)
                                    <option value="{{ e($template->body) }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="sms-mode-tabs">
                        <button type="button" class="mode-btn active" data-mode="single">
                            <i class="fas fa-sms"></i> Tekil SMS
                        </button>
                        <button type="button" class="mode-btn" data-mode="bulk">
                            <i class="fas fa-list"></i> Toplu SMS
                        </button>
                    </div>

                    {{-- Tekil --}}
                    <div id="panel-single" class="sms-form-panel">
                        <form id="form-single">
                            @csrf
                            <div class="form-group">
                                <label>Telefon Numarası</label>
                                <input type="text" name="recipient" id="sms-recipient" class="form-control" placeholder="5551234567">
                            </div>
                            @include('admin.sms.partials.sender-field')
                            <div class="form-group">
                                <label>Mesaj</label>
                                <textarea name="message" id="sms-message" rows="5" maxlength="918" class="form-control"
                                    placeholder="Mesajınızı yazın... Türkçe karakter desteklenir."></textarea>
                                <small class="text-muted">1 segment = 1 SMS hakkı</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-send-modern">
                                <i class="fas fa-paper-plane"></i> SMS Gönder
                            </button>
                        </form>
                    </div>

                    {{-- Toplu --}}
                    <div id="panel-bulk" class="sms-form-panel" style="display:none">
                        <form id="form-bulk">
                            @csrf
                            @if($templates->isNotEmpty())
                                <div class="form-group d-none">
                                    <label>Şablon (üstten seçin)</label>
                                </div>
                            @endif
                            @if($contacts->isNotEmpty())
                                <div class="form-group">
                                    <label>Rehberden Seç</label>
                                    <div class="border rounded p-2 mb-2" style="max-height:120px;overflow-y:auto">
                                        @foreach($contacts as $contact)
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input contact-pick" id="cp_{{ $contact->id }}" value="{{ $contact->phone }}">
                                                <label class="custom-control-label" for="cp_{{ $contact->id }}">{{ $contact->name ?? $contact->phone }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btn-fill-contacts">Seçilenleri Numara Alanına Ekle</button>
                                </div>
                            @endif
                            <div class="form-group">
                                <label>Telefon Numaraları</label>
                                <textarea name="recipients" id="sms-recipients" rows="6" class="form-control"
                                    placeholder="Her satıra bir numara&#10;5551234567&#10;5559876543"></textarea>
                                <small class="text-muted">En fazla {{ $maxBatchSize }} numara</small>
                            </div>
                            @include('admin.sms.partials.sender-field', ['senderFieldId' => 'bulk'])
                            <div class="form-group">
                                <label>Mesaj</label>
                                <textarea name="message" rows="5" maxlength="918" class="form-control sms-bulk-message"
                                    placeholder="Toplu mesaj metni..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-send-modern">
                                <i class="fas fa-paper-plane"></i> Toplu SMS Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sağ: Önizleme + Terminal --}}
            <div class="col-lg-5 mb-4">
                <div class="sms-preview-panel">
                    <div class="sms-phone-mockup">
                        <div class="phone-header">
                            <span>SMS Önizleme</span>
                            <span id="phone-time">{{ now()->format('H:i') }}</span>
                        </div>
                        <div class="phone-sender" id="phone-sender">{{ $defaultSenderId }}</div>
                        <div class="phone-bubble" id="phone-bubble">Mesajınız burada görünecek...</div>
                        <div class="phone-meta" id="phone-meta">1 segment · 1 SMS hakkı kullanılacak</div>
                    </div>

                    <div class="sms-terminal">
                        <div class="sms-terminal-header">
                            <span class="dot dot-red"></span>
                            <span class="dot dot-yellow"></span>
                            <span class="dot dot-green"></span>
                            <span class="title">sms-terminal — gönderim konsolu</span>
                        </div>
                        <div class="sms-live-stats px-3 py-2" style="background:#161b22;border-bottom:1px solid #30363d">
                            <div class="sms-live-stat">
                                <div class="val" id="stat-chars">0</div>
                                <div class="lbl">Karakter</div>
                            </div>
                            <div class="sms-live-stat">
                                <div class="val" id="stat-segments">1</div>
                                <div class="lbl">Segment</div>
                            </div>
                            <div class="sms-live-stat">
                                <div class="val" id="stat-encoding">GSM</div>
                                <div class="lbl">Kodlama</div>
                            </div>
                        </div>
                        <div class="sms-terminal-body" id="terminal-body">
                            <div class="line line-muted">Bağlantı kuruluyor...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    window.SMS_TERMINAL_CONFIG = {
        previewUrl: @json(route('admin.sms.send.preview')),
        sendUrl: @json(route('admin.sms.send.store')),
        bulkUrl: @json(route('admin.sms.send.bulk')),
        csrfToken: @json(csrf_token()),
        showUpstream: @json($showUpstreamBalance ?? false),
        balanceUnit: @json($balanceUnit ?? 'SMS'),
    };
</script>
<script src="{{ asset('js/sms-terminal.js') }}"></script>
<script>
    document.querySelectorAll('.sender-id-field').forEach(function(el) {
        el.addEventListener('change', function() {
            var s = document.getElementById('phone-sender');
            if (s) s.textContent = this.value;
        });
    });

    var templateSelect = document.getElementById('template-select');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            if (!this.value) return;
            var single = document.getElementById('sms-message');
            var bulk = document.querySelector('.sms-bulk-message');
            if (single) {
                single.value = this.value;
                single.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (bulk) bulk.value = this.value;
        });
    }

    var fillBtn = document.getElementById('btn-fill-contacts');
    if (fillBtn) {
        fillBtn.addEventListener('click', function() {
            var phones = [];
            document.querySelectorAll('.contact-pick:checked').forEach(function(el) {
                phones.push(el.value);
            });
            var area = document.getElementById('sms-recipients');
            if (area && phones.length) {
                var existing = area.value.trim();
                area.value = existing ? existing + '\n' + phones.join('\n') : phones.join('\n');
            }
        });
    }
</script>
@stop
