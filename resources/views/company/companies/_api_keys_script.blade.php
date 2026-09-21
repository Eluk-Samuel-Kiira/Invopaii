<script>
(function () {
    'use strict';

    let apiKeysCompanyId = null;
    let apiKeysList = [];

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    /* ═════════════ OPEN ═════════════ */
    window.openApiKeys = function (companyId, companyName) {
        if (!companyId) {
            window.showToast('error', 'Missing company ID');
            return;
        }

        apiKeysCompanyId = companyId;
        const nameEl = document.getElementById('api_keys_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        loadApiKeys(companyId);
        loadApiLogs(1);

        new bootstrap.Modal(document.getElementById('kt_modal_api_keys')).show();
    };

    /* ═════════════ API KEYS ═════════════ */
    function loadApiKeys(companyId) {
        const loading = document.getElementById('api_keys_loading');
        const empty = document.getElementById('api_keys_empty');
        const container = document.getElementById('api_keys_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/api-keys`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                apiKeysList = data.data || [];
                const c = document.getElementById('api_keys_count');
                if (c) c.textContent = apiKeysList.length;

                if (!apiKeysList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderApiKeys();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load API keys');
            });
    }

    function renderApiKeys() {
        const body = document.getElementById('api_keys_body');
        if (!body) return;
        body.innerHTML = '';

        apiKeysList.forEach(k => {
            let actions = '';

            if (k.status === 'active') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="rotateApiKey(${k.id})" title="Rotate" style="width:28px;height:28px;"><i class="ki-duotone ki-arrows-circle fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>`;
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light" onclick="revokeApiKey(${k.id}, '${h(k.name || k.masked_key).replace(/'/g, "\\'")}')" title="Revoke" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;
            }

            const lastUsed = k.last_used_at
                ? `<div>${h(k.last_used_at)}</div><div class="text-muted fs-8">${h(k.last_used_ip || '')}</div>`
                : '<span class="text-muted">Never</span>';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(k.name || '—')}</div>
                    <div class="text-muted fs-8">${h(k.created_by || '')}</div>
                </td>
                <td><span class="font-monospace fs-7">${h(k.masked_key)}</span></td>
                <td><span class="badge badge-light-${k.mode_badge.tone}">${h(k.mode_badge.label)}</span></td>
                <td><span class="badge badge-light-dark">${h(k.type)}</span></td>
                <td>${lastUsed}</td>
                <td><span class="badge badge-light-${k.status_badge.tone}">${h(k.status_badge.label)}</span></td>
                <td class="text-end">${actions || '<span class="text-muted fs-8">—</span>'}</td>
            `;
            body.appendChild(tr);
        });
    }

    window.openAddApiKey = function () {
        document.getElementById('api_key_company_id').value = apiKeysCompanyId;
        document.getElementById('apiKeyForm').reset();
        new bootstrap.Modal(document.getElementById('kt_modal_add_api_key')).show();
    };

    document.getElementById('apiKeyForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('apiKeySaveBtn');
        window.showButtonSpinner(btn);

        const companyId = document.getElementById('api_key_company_id').value;
        const payload = {
            name: document.getElementById('api_key_name').value || null,
            mode: document.getElementById('api_key_mode').value,
            type: document.getElementById('api_key_type').value,
            rate_limit_per_minute: document.getElementById('api_key_rate_limit').value || null,
            expires_at: document.getElementById('api_key_expires_at').value || null,
        };

        fetch(`/admin/companies/${companyId}/api-keys`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_api_key'))?.hide();

                // Show the plaintext reveal
                document.getElementById('reveal_api_key_value').value = data.data.plaintext;
                new bootstrap.Modal(document.getElementById('kt_modal_reveal_api_key')).show();

                window.showToast('success', data.message);
            } else {
                window.showToast('error', data.message || 'Generation failed');
            }
        })
        .catch(() => window.showToast('error', 'Generation failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.copyApiKey = function () {
        const input = document.getElementById('reveal_api_key_value');
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            window.showToast('success', 'Copied to clipboard');
        }).catch(() => {
            document.execCommand('copy');
            window.showToast('success', 'Copied');
        });
    };

    window.revokeApiKey = function (id, label) {
        if (!confirm(`Revoke "${label}"?\n\nRequests using this key will start failing immediately. This cannot be undone.`)) return;

        fetch(`/admin/api-keys/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadApiKeys(apiKeysCompanyId);
            } else window.showToast('error', d.message);
        });
    };

    window.rotateApiKey = function (id) {
        if (!confirm('Rotate this key?\n\nThe old key will be revoked immediately, and a new one will be shown once.')) return;

        fetch(`/admin/api-keys/${id}/rotate`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.getElementById('reveal_api_key_value').value = d.data.plaintext;
                new bootstrap.Modal(document.getElementById('kt_modal_reveal_api_key')).show();
                window.showToast('success', d.message);
            } else window.showToast('error', d.message);
        });
    };

    /* ═════════════ API LOGS ═════════════ */
    let currentLogPage = 1;

    window.loadApiLogs = function (page) {
        currentLogPage = page || 1;

        const loading = document.getElementById('api_logs_loading');
        const empty = document.getElementById('api_logs_empty');
        const container = document.getElementById('api_logs_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams({
            page: currentLogPage,
            per_page: 25,
        });
        const mode = document.getElementById('logFilterMode')?.value;
        const status = document.getElementById('logFilterStatus')?.value;
        if (mode) params.set('mode', mode);
        if (status) params.set('status', status);

        fetch(`/admin/companies/${apiKeysCompanyId}/api-logs?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                const logs = data.data || [];

                if (!logs.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderApiLogs(logs);
                renderLogsPagination(data);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load logs');
            });
    };

    function renderApiLogs(logs) {
        const body = document.getElementById('api_logs_body');
        if (!body) return;
        body.innerHTML = '';

        logs.forEach(l => {
            const tone = l.status_code >= 500 ? 'danger'
                : l.status_code >= 400 ? 'warning'
                : l.status_code >= 200 ? 'success'
                : 'secondary';

            const key = l.api_key
                ? `${l.api_key.key_prefix}…${l.api_key.last_four}`
                : '—';

            const modeBadge = l.mode
                ? `<span class="badge badge-light-${l.mode === 'live' ? 'danger' : 'info'} fs-8">${h(l.mode)}</span>`
                : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="badge badge-light-dark font-monospace">${h(l.method)}</span></td>
                <td>
                    <div class="font-monospace fs-7">${h(l.path)}</div>
                    ${modeBadge}
                </td>
                <td><span class="badge badge-light-${tone}">${l.status_code}</span></td>
                <td><span class="font-monospace fs-8">${h(key)}</span></td>
                <td>${l.duration_ms != null ? l.duration_ms + 'ms' : '—'}</td>
                <td class="text-muted fs-8">${h(l.created_at || '')}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewApiLog(${l.id})" title="View detail" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    function renderLogsPagination(data) {
        const info = document.getElementById('api_logs_info');
        const el = document.getElementById('api_logs_pager');
        if (!el) return;

        el.innerHTML = '';
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); loadApiLogs(page); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.viewApiLog = function (id) {
        const body = document.getElementById('api_log_detail_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_api_log_detail')).show();

        fetch(`/admin/api-logs/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const l = d.data;

                body.innerHTML = `
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><strong>Method:</strong> <span class="font-monospace">${h(l.method)}</span></div>
                        <div class="col-md-6"><strong>Status:</strong> ${l.status_code}</div>
                        <div class="col-md-6"><strong>Path:</strong> <span class="font-monospace">${h(l.path)}</span></div>
                        <div class="col-md-6"><strong>Duration:</strong> ${l.duration_ms ?? '—'}ms</div>
                        <div class="col-md-6"><strong>IP:</strong> ${h(l.ip_address || '—')}</div>
                        <div class="col-md-6"><strong>Time:</strong> ${h(l.created_at || '')}</div>
                        <div class="col-md-6"><strong>Request ID:</strong> <span class="font-monospace fs-8">${h(l.request_id || '—')}</span></div>
                        <div class="col-md-6"><strong>Idempotency:</strong> <span class="font-monospace fs-8">${h(l.idempotency_key || '—')}</span></div>
                    </div>

                    <h4 class="fw-bold mt-5 mb-3">Request Body</h4>
                    <pre class="bg-light p-4 rounded fs-8" style="max-height:200px; overflow:auto;">${h(JSON.stringify(l.request_body, null, 2))}</pre>

                    <h4 class="fw-bold mt-5 mb-3">Response Body</h4>
                    <pre class="bg-light p-4 rounded fs-8" style="max-height:200px; overflow:auto;">${h(JSON.stringify(l.response_body, null, 2))}</pre>

                    ${l.error_message ? `
                        <h4 class="fw-bold mt-5 mb-3 text-danger">Error</h4>
                        <div class="alert alert-danger">
                            <strong>${h(l.error_code || '')}</strong><br>${h(l.error_message)}
                        </div>
                    ` : ''}
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load log</div>';
            });
    };

    document.getElementById('logFilterMode')?.addEventListener('change', () => loadApiLogs(1));
    document.getElementById('logFilterStatus')?.addEventListener('change', () => loadApiLogs(1));

    document.getElementById('kt_modal_reveal_api_key')?.addEventListener('hidden.bs.modal', function () {
        if (apiKeysCompanyId) {
            loadApiKeys(apiKeysCompanyId);
        }
    });

})();
</script>