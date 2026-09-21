<script>
(function () {
    'use strict';

    let webhooksCompanyId = null;
    let webhooksList = [];
    let eventTypeGroups = [];
    let currentDeliveryPage = 1;

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function qs(s) {
        return String(s || '').replace(/'/g, "\\'");
    }

    /* ═══════════════════════════════════════════════════════
       OPEN MODAL
       ═══════════════════════════════════════════════════════ */

    window.openWebhooks = function (companyId, companyName) {
        if (!companyId) {
            window.showToast('error', 'Missing company ID');
            return;
        }

        webhooksCompanyId = companyId;
        const nameEl = document.getElementById('webhooks_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        loadEventTypes();
        loadWebhookEndpoints(companyId);
        loadWebhookDeliveries(1);

        new bootstrap.Modal(document.getElementById('kt_modal_webhooks')).show();
    };

    /* ═══════════════════════════════════════════════════════
       EVENT TYPES
       ═══════════════════════════════════════════════════════ */

    function loadEventTypes() {
        if (eventTypeGroups.length) {
            renderEventGrid();
            return;
        }

        fetch('{{ route("admin.webhook-event-types") }}')
            .then(r => r.json())
            .then(data => {
                eventTypeGroups = data.data || [];
                renderEventGrid();
            })
            .catch(() => {
                const grid = document.getElementById('wh_event_grid');
                if (grid) grid.innerHTML = '<div class="text-danger p-3">Failed to load event types.</div>';
            });
    }

    function renderEventGrid() {
        const grid = document.getElementById('wh_event_grid');
        if (!grid) return;

        grid.innerHTML = '';

        eventTypeGroups.forEach(group => {
            const section = document.createElement('div');
            section.className = 'mb-5';

            const header = document.createElement('div');
            header.className = 'd-flex align-items-center mb-3';
            header.innerHTML = `
                <div class="form-check form-check-custom form-check-solid me-3">
                    <input class="form-check-input" type="checkbox" onchange="toggleResourceGroup('${qs(group.resource)}', this.checked)" />
                </div>
                <span class="fw-bold text-uppercase fs-7 text-muted">${h(group.resource.replace(/_/g, ' '))}</span>
            `;
            section.appendChild(header);

            const list = document.createElement('div');
            list.className = 'ps-5';

            group.types.forEach(t => {
                const row = document.createElement('div');
                row.className = 'form-check form-check-custom form-check-solid mb-2';
                row.innerHTML = `
                    <input class="form-check-input wh-event-checkbox"
                           type="checkbox"
                           id="wh_evt_${h(t.name).replace(/\./g, '_')}"
                           value="${h(t.name)}"
                           data-resource="${h(group.resource)}" />
                    <label class="form-check-label" for="wh_evt_${h(t.name).replace(/\./g, '_')}">
                        <span class="fw-semibold fs-7">${h(t.name)}</span>
                        <span class="text-muted fs-8 ms-2">${h(t.description || '')}</span>
                    </label>
                `;
                list.appendChild(row);
            });

            section.appendChild(list);
            grid.appendChild(section);
        });
    }

    window.toggleResourceGroup = function (resource, checked) {
        document.querySelectorAll(`.wh-event-checkbox[data-resource="${resource}"]`)
            .forEach(el => { el.checked = checked; });
        updateSelectAllLabel();
    };

    window.toggleAllEvents = function () {
        const boxes = document.querySelectorAll('.wh-event-checkbox');
        const allChecked = Array.from(boxes).every(b => b.checked);
        boxes.forEach(b => { b.checked = !allChecked; });
        updateSelectAllLabel();
    };

    function updateSelectAllLabel() {
        const label = document.getElementById('wh_toggle_all_label');
        if (!label) return;

        const boxes = document.querySelectorAll('.wh-event-checkbox');
        const allChecked = boxes.length > 0 && Array.from(boxes).every(b => b.checked);
        label.textContent = allChecked ? 'Clear all' : 'Select all';
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('wh-event-checkbox')) {
            updateSelectAllLabel();
        }
    });

    function getSelectedEvents() {
        return Array.from(document.querySelectorAll('.wh-event-checkbox'))
            .filter(b => b.checked)
            .map(b => b.value);
    }

    function setSelectedEvents(eventList) {
        // If '*' is present, check everything
        const wantsAll = eventList.includes('*');
        document.querySelectorAll('.wh-event-checkbox').forEach(b => {
            b.checked = wantsAll || eventList.includes(b.value);
        });
        updateSelectAllLabel();
    }

    /* ═══════════════════════════════════════════════════════
       ENDPOINTS
       ═══════════════════════════════════════════════════════ */

    function loadWebhookEndpoints(companyId) {
        const loading = document.getElementById('wh_endpoints_loading');
        const empty = document.getElementById('wh_endpoints_empty');
        const container = document.getElementById('wh_endpoints_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/webhook-endpoints`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                webhooksList = data.data || [];
                const c = document.getElementById('wh_endpoints_count');
                if (c) c.textContent = webhooksList.length;

                if (!webhooksList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderEndpoints();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load endpoints');
            });
    }

    function renderEndpoints() {
        const body = document.getElementById('wh_endpoints_body');
        if (!body) return;
        body.innerHTML = '';

        webhooksList.forEach(e => {
            const eventsLabel = (e.enabled_events || []).includes('*')
                ? '<span class="badge badge-light-primary">All events</span>'
                : `<span class="badge badge-light-dark">${(e.enabled_events || []).length} event${(e.enabled_events || []).length === 1 ? '' : 's'}</span>`;

            const lastSuccess = e.last_success_at
                ? `<div>${h(e.last_success_at)}</div>`
                : '<span class="text-muted">Never</span>';

            const failures = e.consecutive_failures > 0
                ? `<span class="badge badge-light-danger">${e.consecutive_failures}</span>`
                : '<span class="text-muted">0</span>';

            let actions = '';

            // Toggle enable/disable
            if (e.status === 'enabled') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="toggleWebhook(${e.id})" title="Disable" style="width:28px;height:28px;"><i class="ki-duotone ki-minus-circle fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>`;
            } else {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="toggleWebhook(${e.id})" title="Enable" style="width:28px;height:28px;"><i class="ki-duotone ki-check fs-4"></i></button>`;
            }

            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="rotateWebhookSecret(${e.id})" title="Rotate secret" style="width:28px;height:28px;"><i class="ki-duotone ki-key fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>`;

            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editWebhook(${e.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;

            actions += `<button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteWebhook(${e.id})" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="font-monospace fs-7">${h(e.url)}</div>
                    ${e.description ? `<div class="text-muted fs-8">${h(e.description)}</div>` : ''}
                </td>
                <td><span class="badge badge-light-${e.mode_badge.tone}">${h(e.mode_badge.label)}</span></td>
                <td>${eventsLabel}</td>
                <td><span class="badge badge-light-${e.status_badge.tone}">${h(e.status_badge.label)}</span></td>
                <td>${lastSuccess}</td>
                <td>${failures}</td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    /* ═══════════════════════════════════════════════════════
       ADD / EDIT ENDPOINT
       ═══════════════════════════════════════════════════════ */

    window.openAddWebhook = function () {
        document.getElementById('wh_modal_title').textContent = 'Add Endpoint';
        document.getElementById('wh_id').value = '';
        document.getElementById('wh_company_id').value = webhooksCompanyId;
        document.getElementById('webhookForm').reset();

        loadEventTypes();
        // clear all checkboxes
        setTimeout(() => setSelectedEvents([]), 50);

        new bootstrap.Modal(document.getElementById('kt_modal_add_webhook')).show();
    };

    window.editWebhook = function (id) {
        const e = webhooksList.find(x => x.id === id);
        if (!e) return;

        document.getElementById('wh_modal_title').textContent = 'Edit Endpoint';
        document.getElementById('wh_id').value = e.id;
        document.getElementById('wh_company_id').value = e.company_id;
        document.getElementById('wh_url').value = e.url || '';
        document.getElementById('wh_mode').value = e.mode || 'test';
        document.getElementById('wh_description').value = e.description || '';
        document.getElementById('wh_timeout').value = e.timeout_seconds || 10;
        document.getElementById('wh_max_attempts').value = e.max_attempts || 8;

        loadEventTypes();
        // Apply selection after the grid renders (or immediately if already rendered)
        setTimeout(() => setSelectedEvents(e.enabled_events || []), 50);

        new bootstrap.Modal(document.getElementById('kt_modal_add_webhook')).show();
    };

    document.getElementById('webhookForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('whSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('wh_id').value;
        const companyId = document.getElementById('wh_company_id').value;

        const selected = getSelectedEvents();

        if (!selected.length) {
            window.showToast('warning', 'Select at least one event to subscribe to.');
            window.hideButtonSpinner(btn);
            return;
        }

        const payload = {
            url: document.getElementById('wh_url').value,
            mode: document.getElementById('wh_mode').value,
            description: document.getElementById('wh_description').value || null,
            enabled_events: selected,
            timeout_seconds: parseInt(document.getElementById('wh_timeout').value) || 10,
            max_attempts: parseInt(document.getElementById('wh_max_attempts').value) || 8,
        };

        const url = id
            ? `/admin/webhook-endpoints/${id}`
            : `/admin/companies/${companyId}/webhook-endpoints`;

        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
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
                window.showToast('success', data.message);

                // Creating → show secret once
                if (!id && data.data.plaintext_secret) {
                    bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_webhook'))?.hide();
                    document.getElementById('reveal_webhook_secret_value').value = data.data.plaintext_secret;
                    new bootstrap.Modal(document.getElementById('kt_modal_reveal_webhook_secret')).show();
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_webhook'))?.hide();
                    loadWebhookEndpoints(companyId);
                }
            } else {
                window.showToast('error', data.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ═══════════════════════════════════════════════════════
       ACTIONS
       ═══════════════════════════════════════════════════════ */

    window.toggleWebhook = function (id) {
        fetch(`/admin/webhook-endpoints/${id}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadWebhookEndpoints(webhooksCompanyId);
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to toggle'));
    };

    window.deleteWebhook = function (id) {
        if (!confirm('Delete this endpoint?\n\nPast deliveries remain in the log but no new events will be sent.')) return;

        fetch(`/admin/webhook-endpoints/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadWebhookEndpoints(webhooksCompanyId);
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to delete'));
    };

    window.rotateWebhookSecret = function (id) {
        if (!confirm('Rotate the signing secret?\n\nSignatures using the old secret will start failing immediately. Your integration must be updated.')) return;

        fetch(`/admin/webhook-endpoints/${id}/rotate-secret`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.getElementById('reveal_webhook_secret_value').value = d.data.plaintext_secret;
                new bootstrap.Modal(document.getElementById('kt_modal_reveal_webhook_secret')).show();
                window.showToast('success', d.message);
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to rotate secret'));
    };

    window.copyWebhookSecret = function () {
        const input = document.getElementById('reveal_webhook_secret_value');
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            window.showToast('success', 'Copied to clipboard');
        }).catch(() => {
            document.execCommand('copy');
            window.showToast('success', 'Copied');
        });
    };

    /* ═══════════════════════════════════════════════════════
       DELIVERIES
       ═══════════════════════════════════════════════════════ */

    window.loadWebhookDeliveries = function (page) {
        currentDeliveryPage = page || 1;

        const loading = document.getElementById('wh_deliveries_loading');
        const empty = document.getElementById('wh_deliveries_empty');
        const container = document.getElementById('wh_deliveries_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams({
            page: currentDeliveryPage,
            per_page: 25,
        });
        const status = document.getElementById('whFilterStatus')?.value;
        if (status) params.set('status', status);

        fetch(`/admin/companies/${webhooksCompanyId}/webhook-deliveries?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                const deliveries = data.data || [];

                if (!deliveries.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderDeliveries(deliveries);
                renderDeliveriesPagination(data);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load deliveries');
            });
    };

    function renderDeliveries(deliveries) {
        const body = document.getElementById('wh_deliveries_body');
        if (!body) return;
        body.innerHTML = '';

        deliveries.forEach(d => {
            const tone = d.status_badge ? d.status_badge.tone : 'secondary';
            const label = d.status_badge ? d.status_badge.label : 'Unknown';

            const eventLabel = d.event?.type || d.event_type || '—';
            const endpointUrl = d.endpoint?.url || '—';

            const retryBtn = d.status !== 'succeeded'
                ? `<button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="retryDelivery(${d.id})" title="Retry" style="width:28px;height:28px;"><i class="ki-duotone ki-arrows-circle fs-4"><span class="path1"></span><span class="path2"></span></i></button>`
                : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="badge badge-light-dark font-monospace fs-8">${h(eventLabel)}</span></td>
                <td><div class="font-monospace fs-8 text-truncate" style="max-width:280px;" title="${h(endpointUrl)}">${h(endpointUrl)}</div></td>
                <td><span class="badge badge-light-${tone}">${h(label)}</span></td>
                <td>${d.attempt}</td>
                <td>${d.response_code != null ? d.response_code : '—'}</td>
                <td>${d.duration_ms != null ? d.duration_ms + 'ms' : '—'}</td>
                <td class="text-muted fs-8">${h(d.created_at || '')}</td>
                <td class="text-end">
                    ${retryBtn}
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewDelivery(${d.id})" title="View detail" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    function renderDeliveriesPagination(data) {
        const info = document.getElementById('wh_deliveries_info');
        const el = document.getElementById('wh_deliveries_pager');
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
            if (!isDisabled) a.onclick = (ev) => { ev.preventDefault(); loadWebhookDeliveries(page); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.viewDelivery = function (id) {
        const body = document.getElementById('delivery_detail_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_delivery_detail')).show();

        fetch(`/admin/webhook-deliveries/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const x = d.data;

                const tone = x.status_badge ? x.status_badge.tone : 'secondary';
                const label = x.status_badge ? x.status_badge.label : 'Unknown';

                body.innerHTML = `
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><strong>Event:</strong> <span class="font-monospace">${h(x.event_type)}</span></div>
                        <div class="col-md-6"><strong>Status:</strong> <span class="badge badge-light-${tone}">${h(label)}</span></div>
                        <div class="col-md-6"><strong>Endpoint:</strong> <span class="font-monospace fs-8">${h(x.endpoint?.url || '—')}</span></div>
                        <div class="col-md-6"><strong>Attempt:</strong> ${x.attempt}</div>
                        <div class="col-md-6"><strong>Response code:</strong> ${x.response_code ?? '—'}</div>
                        <div class="col-md-6"><strong>Duration:</strong> ${x.duration_ms ?? '—'}ms</div>
                        <div class="col-md-6"><strong>Scheduled:</strong> ${h(x.scheduled_for || '—')}</div>
                        <div class="col-md-6"><strong>Delivered:</strong> ${h(x.delivered_at || '—')}</div>
                    </div>

                    ${x.error_message ? `
                        <h4 class="fw-bold mt-5 mb-3 text-danger">Error</h4>
                        <div class="alert alert-danger">
                            <strong>${h(x.error_class || '')}</strong><br>${h(x.error_message)}
                        </div>
                    ` : ''}

                    <h4 class="fw-bold mt-5 mb-3">Webhook Payload</h4>
                    <pre class="bg-light p-4 rounded fs-8" style="max-height:300px; overflow:auto;">${h(JSON.stringify(x.payload, null, 2))}</pre>

                    <h4 class="fw-bold mt-5 mb-3">Response Body</h4>
                    <pre class="bg-light p-4 rounded fs-8" style="max-height:200px; overflow:auto;">${h(x.response_body || '(empty)')}</pre>
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load delivery</div>';
            });
    };

    window.retryDelivery = function (id) {
        if (!confirm('Retry this delivery now?')) return;

        fetch(`/admin/webhook-deliveries/${id}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadWebhookDeliveries(currentDeliveryPage);
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to retry'));
    };

    /* ═══════════════════════════════════════════════════════
       FILTERS + MODAL CLOSE HOOKS
       ═══════════════════════════════════════════════════════ */

    document.getElementById('whFilterStatus')?.addEventListener('change', () => loadWebhookDeliveries(1));

    document.getElementById('kt_modal_reveal_webhook_secret')?.addEventListener('hidden.bs.modal', function () {
        if (webhooksCompanyId) {
            loadWebhookEndpoints(webhooksCompanyId);
        }
    });

})();
</script>