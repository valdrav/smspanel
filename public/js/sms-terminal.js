/**
 * SMS Terminal — canlı önizleme ve AJAX gönderim
 */
(function () {
    'use strict';

    const config = window.SMS_TERMINAL_CONFIG || {};
    let previewTimeout = null;

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    function terminalLog(message, type = 'info') {
        const body = $('#terminal-body');
        if (!body) return;
        const time = new Date().toLocaleTimeString('tr-TR');
        const line = document.createElement('div');
        line.className = 'line line-' + type;
        line.innerHTML = '<span class="line-muted">[' + time + ']</span> ' + message;
        body.appendChild(line);
        body.scrollTop = body.scrollHeight;
    }

    function updateBalance(balance) {
        const el = $('#sms-balance-value');
        if (el && typeof balance === 'number') {
            el.textContent = new Intl.NumberFormat('tr-TR').format(balance);
        }
    }

    function updatePreview(data) {
        const bubble = $('#phone-bubble');
        const meta = $('#phone-meta');
        const statChars = $('#stat-chars');
        const statSegments = $('#stat-segments');
        const statEncoding = $('#stat-encoding');

        if (bubble) bubble.textContent = data.message || 'Mesajınız burada görünecek...';
        if (statChars) statChars.textContent = data.chars || 0;
        if (statSegments) statSegments.textContent = data.segments || 1;
        if (statEncoding) statEncoding.textContent = data.encoding === 'unicode' ? 'Unicode' : 'GSM';
        if (meta) {
            let text = (data.segments || 1) + ' segment · ' + (data.credits || 1) + ' SMS hakkı kullanılacak';
            if (typeof data.balance === 'number') {
                text += ' · Kalan: ' + data.balance;
            }
            if (data.can_afford === false) {
                text += ' (yetersiz hak)';
            }
            meta.textContent = text;
        }
        if (typeof data.balance === 'number') {
            updateBalance(data.balance);
        }
    }

    function fetchPreview(message, recipient) {
        if (!config.previewUrl) return;

        fetch(config.previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: message, recipient: recipient }),
        })
            .then(r => r.json())
            .then(data => {
                updatePreview({
                    message: message,
                    chars: data.chars,
                    segments: data.segments,
                    credits: data.credits,
                    encoding: data.encoding,
                    balance: data.balance,
                    can_afford: data.can_afford,
                });

                if (recipient) {
                    if (data.recipient_valid) {
                        terminalLog('Numara geçerli: ' + recipient, 'success');
                    } else if (recipient.length > 5) {
                        terminalLog('Geçersiz numara formatı: ' + recipient, 'warning');
                    }
                }

                if (data.can_afford === false) {
                    terminalLog('Uyarı: Kalan SMS hakkınız bu mesaj için yetersiz (' + data.balance + ').', 'warning');
                }

                if (data.texcell_sync_error) {
                    terminalLog('Texcell bakiye senkronu başarısız: ' + data.texcell_sync_error, 'warning');
                } else if (data.texcell_synced && typeof data.balance === 'number' && data.balance > 0) {
                    terminalLog('Texcell bakiyesi güncellendi → ' + data.balance + ' SMS hakkı.', 'success');
                }

                terminalLog('Segment: ' + data.segments + ' · Kodlama: ' + (data.encoding === 'unicode' ? 'Unicode (TR)' : 'GSM') + ' · Hak: ' + data.balance, 'info');
            })
            .catch(() => terminalLog('Önizleme alınamadı.', 'error'));
    }

    function onInputChange() {
        const activePanel = document.querySelector('.sms-form-panel:not([style*="display: none"])') || document.querySelector('#panel-single');
        const messageEl = activePanel?.querySelector('[name=message]') || $('#sms-message');
        const singleRecipient = activePanel?.querySelector('[name=recipient]');
        const bulkRecipients = activePanel?.querySelector('[name=recipients]');
        const message = messageEl?.value || '';

        let recipient = '';
        if (singleRecipient) {
            recipient = singleRecipient.value || '';
        } else if (bulkRecipients && bulkRecipients.value.trim()) {
            recipient = bulkRecipients.value.trim().split(/\r?\n/).map(s => s.trim()).filter(Boolean)[0] || '';
        }

        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(() => fetchPreview(message, recipient), 300);
    }

    function switchMode(mode) {
        $$('.mode-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.mode === mode));
        $$('.sms-form-panel').forEach(p => p.style.display = 'none');
        const panel = $('#panel-' + mode);
        if (panel) panel.style.display = 'block';
        terminalLog('Mod: ' + (mode === 'single' ? 'Tekil SMS' : 'Toplu SMS'), 'info');
        onInputChange();
    }

    function extractErrorMessage(data, fallback) {
        if (data && data.message) return data.message;
        if (data && data.errors) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first[0]) return first[0];
        }
        return fallback || 'Gönderim başarısız';
    }

    function reportResults(data) {
        terminalLog((data.success ? '✓ ' : '✗ ') + (data.message || 'İşlem tamamlandı.'), data.success ? 'success' : 'error');

        if (typeof data.balance === 'number') {
            updateBalance(data.balance);
            terminalLog('Kalan SMS hakkı: ' + data.balance, 'info');
        }

        if (data.sent) terminalLog('Gönderilen: ' + data.sent, 'success');
        if (data.failed) terminalLog('Başarısız: ' + data.failed, 'error');
        if (data.queued) terminalLog('Kuyrukta kalan: ' + data.queued, 'warning');

        (data.items || []).slice(0, 20).forEach(function (item) {
            if (item.status === 'sent') {
                terminalLog('#' + item.id + ' ' + item.recipient + ' → Gönderildi', 'success');
            } else if (item.status === 'failed') {
                terminalLog('#' + item.id + ' ' + item.recipient + ' → Hata: ' + (item.error_message || 'bilinmiyor'), 'error');
            } else {
                terminalLog('#' + item.id + ' ' + item.recipient + ' → ' + (item.status_label || item.status), 'warning');
            }
        });

        if ((data.items || []).length > 20) {
            terminalLog('... ve ' + (data.items.length - 20) + ' kayıt daha (SMS Geçmişi’nden bakın)', 'muted');
        }
    }

    function sendSms(form, url) {
        const btn = form.querySelector('[type=submit]');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...'; }

        terminalLog('> Gönderim başlatılıyor...', 'prompt');

        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(extractErrorMessage(data));
                return data;
            })
            .then(data => {
                reportResults(data);
                form.reset();
                // Toplu gönderim sonrası tekil alandaki eski numarayı da temizle
                const singleRecipient = $('#sms-recipient');
                if (singleRecipient) singleRecipient.value = '';
                onInputChange();
            })
            .catch(err => {
                terminalLog('✗ Hata: ' + err.message, 'error');
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = form.id === 'form-bulk'
                        ? '<i class="fas fa-paper-plane"></i> Toplu SMS Gönder'
                        : '<i class="fas fa-paper-plane"></i> SMS Gönder';
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!config.previewUrl) return;

        terminalLog('SMS Terminal hazır. Haklar paket/onay ile yüklenir; gönderimde düşer.', 'success');

        $$('.mode-btn').forEach(btn => {
            btn.addEventListener('click', () => switchMode(btn.dataset.mode));
        });

        ['#sms-message', '#sms-recipient', '#sms-recipients', '.sms-bulk-message'].forEach(sel => {
            const el = $(sel);
            if (el) el.addEventListener('input', onInputChange);
        });

        const formSingle = $('#form-single');
        const formBulk = $('#form-bulk');

        if (formSingle) {
            formSingle.addEventListener('submit', function (e) {
                e.preventDefault();
                sendSms(formSingle, config.sendUrl);
            });
        }

        if (formBulk) {
            formBulk.addEventListener('submit', function (e) {
                e.preventDefault();
                sendSms(formBulk, config.bulkUrl);
            });
        }

        onInputChange();
    });
})();
