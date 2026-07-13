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
            meta.textContent = (data.segments || 1) + ' segment · ' + (data.credits || 1) + ' SMS hakkı kullanılacak';
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
                });

                if (recipient) {
                    if (data.recipient_valid) {
                        terminalLog('Numara geçerli: ' + recipient, 'success');
                    } else if (recipient.length > 5) {
                        terminalLog('Geçersiz numara formatı: ' + recipient, 'warning');
                    }
                }

                terminalLog('Segment: ' + data.segments + ' · Kodlama: ' + (data.encoding === 'unicode' ? 'Unicode (TR)' : 'GSM'), 'info');
            })
            .catch(() => terminalLog('Önizleme alınamadı.', 'error'));
    }

    function onInputChange() {
        const activePanel = document.querySelector('.sms-form-panel:not([style*="display: none"])') || document.querySelector('#panel-single');
        const messageEl = activePanel?.querySelector('[name=message]') || $('#sms-message');
        const recipientEl = activePanel?.querySelector('[name=recipient]') || $('#sms-recipient');
        const message = messageEl?.value || '';
        const recipient = recipientEl?.value || '';

        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(() => fetchPreview(message, recipient), 300);
    }

    function switchMode(mode) {
        $$('.mode-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.mode === mode));
        $$('.sms-form-panel').forEach(p => p.style.display = 'none');
        const panel = $('#panel-' + mode);
        if (panel) panel.style.display = 'block';
        terminalLog('Mod: ' + (mode === 'single' ? 'Tekil SMS' : 'Toplu SMS'), 'info');
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
                if (!r.ok) throw new Error(data.message || 'Gönderim başarısız');
                return data;
            })
            .then(data => {
                terminalLog('✓ ' + (data.message || 'SMS kuyruğa alındı.'), 'success');
                if (data.data) {
                    terminalLog('  ID: #' + data.data.id + ' → ' + data.data.recipient + ' (' + data.data.segments + ' segment)', 'success');
                }
                if (data.count) {
                    terminalLog('  Toplam: ' + data.count + ' SMS kuyruğa alındı', 'success');
                }
                form.reset();
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

        terminalLog('SMS Terminal v1.0 hazır.', 'success');
        terminalLog('Mesaj yazmaya başlayın — canlı önizleme aktif.', 'muted');

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
