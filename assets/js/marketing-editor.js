(function () {
    'use strict';

    var templates = window.BB_MARKETING_TEMPLATES || {};
    var defaults = window.BB_MARKETING_DEFAULTS || {};

    function getField(id) {
        return document.getElementById(id);
    }

    function fieldVal(id) {
        var el = getField(id);
        return el ? el.value.trim() : '';
    }

    function applyPlaceholders(html) {
        var vars = {
            HEADLINE: fieldVal('fld-headline') || defaults.HEADLINE,
            PREHEADER: fieldVal('fld-preheader') || defaults.PREHEADER,
            BODY: defaults.BODY,
            IMAGE_URL: fieldVal('fld-image-url') || defaults.IMAGE_URL,
            IMAGE_LINK: fieldVal('fld-image-link') || defaults.IMAGE_LINK,
            CTA_TEXT: fieldVal('fld-cta-text') || defaults.CTA_TEXT,
            CTA_URL: fieldVal('fld-cta-url') || defaults.CTA_URL,
            OFFER_CODE: fieldVal('fld-offer-code') || defaults.OFFER_CODE
        };
        var out = html;
        Object.keys(vars).forEach(function (key) {
            out = out.split('{{' + key + '}}').join(vars[key]);
        });
        return out;
    }

    function getEditorHtml() {
        if (window.CKEDITOR && CKEDITOR.instances['marketing-editor']) {
            return CKEDITOR.instances['marketing-editor'].getData();
        }
        var ta = getField('marketing-editor');
        return ta ? ta.value : '';
    }

    function setEditorHtml(html) {
        if (window.CKEDITOR && CKEDITOR.instances['marketing-editor']) {
            CKEDITOR.instances['marketing-editor'].setData(html);
        } else {
            var ta = getField('marketing-editor');
            if (ta) ta.value = html;
        }
    }

    function loadTemplate(key) {
        var tpl = templates[key];
        if (!tpl) return;
        if (tpl.subject && getField('campaign-subject')) {
            getField('campaign-subject').value = tpl.subject;
        }
        var body = applyPlaceholders(tpl.html || '');
        body = body.replace('{{BODY}}', defaults.BODY || '<p>Your content here.</p>');
        setEditorHtml(body);
    }

    function initEditor() {
        if (typeof window.CKEDITOR === 'undefined') return false;
        if (CKEDITOR.instances['marketing-editor']) return true;

        CKEDITOR.replace('marketing-editor', {
            customConfig: '',
            versionCheck: false,
            height: 420,
            width: '100%',
            removePlugins: 'elementspath',
            extraPlugins: 'colorbutton,font,justify',
            allowedContent: true,
            toolbar: [
                { name: 'document', items: ['Source', '-', 'Preview', 'Maximize'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                '/',
                { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] }
            ]
        });
        return true;
    }

    function refreshFromFields() {
        var key = getField('template-key') ? getField('template-key').value : 'manual';
        var tpl = templates[key];
        if (!tpl) return;
        var currentBody = getEditorHtml();
        var bodyMatch = currentBody.match(/<div[^>]*>([\s\S]*)<\/div>\s*$/i);
        var savedBody = bodyMatch ? bodyMatch[1] : currentBody;
        var html = applyPlaceholders(tpl.html || '');
        html = html.replace('{{BODY}}', savedBody || defaults.BODY);
        setEditorHtml(html);
    }

    function showPreview() {
        var iframe = getField('preview-frame');
        var modal = getField('preview-modal');
        if (!iframe || !modal) return;
        var sampleEmail = 'preview@bisanibrother.com';
        var body = getEditorHtml();
        var wrapped = window.BB_MARKETING_PREVIEW_WRAP(body, sampleEmail);
        iframe.srcdoc = wrapped;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hidePreview() {
        var modal = getField('preview-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function getRecipientMode() {
        var checked = document.querySelector('input[name="recipient_mode"]:checked');
        return checked ? checked.value : 'all';
    }

    function toggleRecipientPanels() {
        var mode = getRecipientMode();
        var manual = getField('panel-manual');
        var imp = getField('panel-import');
        var bulkOpts = getField('panel-bulk-options');
        if (manual) manual.classList.toggle('hidden', mode !== 'manual');
        if (imp) imp.classList.toggle('hidden', mode !== 'import');
        if (bulkOpts) bulkOpts.classList.toggle('hidden', mode !== 'manual' && mode !== 'import');
    }

    function showCountResult(msgEl, data) {
        if (!msgEl) return;
        msgEl.classList.remove('hidden');
        if (!data.ok || data.count === 0) {
            msgEl.className = 'text-xs text-red-600';
            msgEl.textContent = data.count === 0 ? 'No valid emails found. Check format and try again.' : (data.message || 'Could not parse list.');
            return;
        }
        msgEl.className = 'text-xs text-green-700 font-bold';
        var sample = (data.sample && data.sample.length) ? ' e.g. ' + data.sample.join(', ') : '';
        msgEl.textContent = data.count + ' valid email(s) ready to send.' + sample;
    }

    function countBulkEmails(mode) {
        var fd = new FormData();
        fd.append('action', 'parse_bulk');
        fd.append('recipient_mode', mode);
        fd.append('manual_emails', fieldVal('manual-emails'));
        fd.append('import_emails', fieldVal('import-emails'));
        var csvInput = getField('import-csv');
        if (mode === 'import' && csvInput && csvInput.files && csvInput.files[0]) {
            fd.append('import_csv', csvInput.files[0]);
        }
        return fetch('marketing-campaigns.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); });
    }

    function runBatchSend(campaignId, offset, total) {
        var bar = getField('send-progress-bar');
        var label = getField('send-progress-label');
        var btn = getField('btn-send-campaign');

        return fetch('marketing-send-batch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                campaign_id: campaignId,
                offset: offset,
                limit: 10,
                _csrf: window.BB_CSRF_TOKEN || ''
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) throw new Error(data.message || 'Send failed');
            var pct = data.total ? Math.round((data.next_offset / data.total) * 100) : 100;
            if (bar) bar.style.width = pct + '%';
            if (label) {
                label.textContent = 'Sent ' + data.total_sent + ' / ' + data.total +
                    (data.total_failed ? ' (' + data.total_failed + ' failed)' : '');
            }
            if (!data.done) {
                return runBatchSend(campaignId, data.next_offset, data.total);
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Campaign Sent';
            }
            setTimeout(function () { window.location.href = 'marketing-campaigns.php?tab=history&msg=Campaign+sent+successfully'; }, 1200);
            return data;
        });
    }

    function sendTestEmail() {
        var subjectEl = getField('campaign-subject');
        var testEl = getField('test-email');
        var btn = getField('btn-send-test');
        if (!subjectEl || !testEl) return;

        var subject = subjectEl.value.trim();
        var testEmail = testEl.value.trim();
        var bodyHtml = getEditorHtml();

        if (!testEmail) {
            alert('Enter a test email address.');
            return;
        }
        if (!subject || !bodyHtml.trim()) {
            alert('Subject and email body are required.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending...';
        }

        var fd = new FormData();
        fd.append('action', 'send_test');
        fd.append('campaign_subject', subject);
        fd.append('body_html', bodyHtml);
        fd.append('test_email', testEmail);

        fetch('marketing-campaigns.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var msg = data.message || (data.ok ? 'Test sent.' : 'Test failed.');
            if (data.ok && data.method === 'outbox') {
                msg += '\n\nOpen Admin → Mail Outbox to preview the email.';
            }
            alert(msg);
        })
        .catch(function () {
            alert('Test email request failed.');
        })
        .finally(function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-vial mr-2"></i> Send Test Email';
            }
        });
    }

    function startCampaignSend(form) {
        var btn = getField('btn-send-campaign');
        var mode = getRecipientMode();

        if (mode === 'manual' && !fieldVal('manual-emails')) {
            alert('Please type or paste at least one email address.');
            return;
        }
        if (mode === 'import') {
            var csvInput = getField('import-csv');
            var hasFile = csvInput && csvInput.files && csvInput.files[0];
            if (!hasFile && !fieldVal('import-emails')) {
                alert('Upload a CSV file or paste email list to import.');
                return;
            }
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending...';
        }
        var wrap = getField('send-progress-wrap');
        if (wrap) wrap.classList.remove('hidden');

        var fd = new FormData(form);
        fd.set('action', 'create_and_send');
        if (window.CKEDITOR && CKEDITOR.instances['marketing-editor']) {
            fd.set('body_html', CKEDITOR.instances['marketing-editor'].getData());
        }

        fetch('marketing-campaigns.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) throw new Error(data.message || 'Could not start campaign');
            return runBatchSend(data.campaign_id, 0, data.total);
        })
        .catch(function (err) {
            alert(err.message || 'Campaign send failed.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Send Campaign';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var tries = 0;
        var iv = setInterval(function () {
            if (initEditor() || ++tries > 40) clearInterval(iv);
        }, 100);

        var tplSelect = getField('template-key');
        if (tplSelect) {
            tplSelect.addEventListener('change', function () {
                if (confirm('Load this template? Current editor content will be replaced.')) {
                    loadTemplate(tplSelect.value);
                }
            });
        }

        ['fld-headline', 'fld-preheader', 'fld-image-url', 'fld-image-link', 'fld-cta-text', 'fld-cta-url', 'fld-offer-code'].forEach(function (id) {
            var el = getField(id);
            if (el) el.addEventListener('change', refreshFromFields);
        });

        var btnPreview = getField('btn-preview');
        if (btnPreview) btnPreview.addEventListener('click', showPreview);

        var btnClosePreview = getField('btn-close-preview');
        if (btnClosePreview) btnClosePreview.addEventListener('click', hidePreview);

        var btnTest = getField('btn-send-test');
        if (btnTest) btnTest.addEventListener('click', sendTestEmail);

        document.querySelectorAll('.recipient-mode-radio').forEach(function (radio) {
            radio.addEventListener('change', toggleRecipientPanels);
        });
        toggleRecipientPanels();

        var btnCountManual = getField('btn-count-manual');
        if (btnCountManual) {
            btnCountManual.addEventListener('click', function () {
                countBulkEmails('manual').then(function (data) {
                    showCountResult(getField('manual-count-msg'), data);
                });
            });
        }

        var btnCountImport = getField('btn-count-import');
        if (btnCountImport) {
            btnCountImport.addEventListener('click', function () {
                countBulkEmails('import').then(function (data) {
                    showCountResult(getField('import-count-msg'), data);
                });
            });
        }

        var form = getField('campaign-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!confirm('Send this campaign now via marketing@bisanibrother.com?')) return;
                startCampaignSend(form);
            });
        }

        var btnUpload = getField('btn-upload-image');
        if (btnUpload) {
            btnUpload.addEventListener('click', function () {
                var fileInput = getField('mkt-image-file');
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    alert('Choose an image first.');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload_image');
                fd.append('image', fileInput.files[0]);
                fetch('marketing-campaigns.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok && data.url) {
                        var imgField = getField('fld-image-url');
                        if (imgField) imgField.value = data.url;
                        refreshFromFields();
                        alert('Image uploaded.');
                    } else {
                        alert(data.message || 'Upload failed');
                    }
                });
            });
        }
    });
})();
