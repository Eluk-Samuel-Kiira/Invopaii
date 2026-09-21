<script>
(function () {
    'use strict';

    let subCompanyId = null;
    let subList = [];
    let subCustomers = [];
    let subTaxRates = [];
    let subSearchTimeout = null;

    const ZERO_DECIMAL = ['UGX','RWF','BIF','XOF','XAF','JPY','KRW','VND','CLP','ISK','XPF'];

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function money(minor, currency) {
        const amount = ZERO_DECIMAL.includes(currency) ? minor : minor / 100;
        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: ZERO_DECIMAL.includes(currency) ? 0 : 2,
            }).format(amount);
        } catch {
            return currency + ' ' + amount.toLocaleString();
        }
    }

    function toMinor(value, currency) {
        const num = parseFloat(value) || 0;
        return ZERO_DECIMAL.includes(currency) ? Math.round(num) : Math.round(num * 100);
    }

    function fromMinor(minor, currency) {
        return ZERO_DECIMAL.includes(currency) ? minor : minor / 100;
    }

    /* ══════ OPEN ══════ */

    window.openSubscriptions = function (companyId, companyName) {
        if (!companyId) return;
        subCompanyId = companyId;
        document.getElementById('sub_company_name').textContent = companyName || 'Company';

        document.getElementById('subSearch').value = '';
        document.getElementById('subModeFilter').value = '';
        document.getElementById('subStatusFilter').value = '';

        loadSubCustomers();
        loadSubTaxRates();
        loadSubscriptionStats();
        loadSubscriptions();

        new bootstrap.Modal(document.getElementById('kt_modal_subscriptions')).show();
    };

    /* ══════ CACHES ══════ */

    function loadSubCustomers() {
        fetch(`/admin/companies/${subCompanyId}/customers?per_page=200`)
            .then(r => r.json())
            .then(d => {
                subCustomers = d.data || [];
                const sel = document.getElementById('sub_customer_id');
                if (sel) {
                    sel.innerHTML = '<option value="">Select customer…</option>' +
                        subCustomers.map(c => `<option value="${c.id}">${h(c.display_name || c.name || c.email || c.public_id)}</option>`).join('');
                }
            })
            .catch(() => {});
    }

    function loadSubTaxRates() {
        fetch(`/admin/companies/${subCompanyId}/tax-rates`)
            .then(r => r.json())
            .then(d => {
                subTaxRates = (d.data || []).filter(t => t.is_active);
            })
            .catch(() => {});
    }

    /* ══════ STATS ══════ */

    function loadSubscriptionStats() {
        fetch(`/admin/company2/${subCompanyId}/subscriptions/stats`)
            .then(r => r.json())
            .then(s => {
                document.getElementById('sub_stat_total').textContent = s.total ?? '—';
                document.getElementById('sub_stat_active').textContent = s.active ?? '—';
                document.getElementById('sub_stat_trialing').textContent = s.trialing ?? '—';
                document.getElementById('sub_stat_past_due').textContent = s.past_due ?? '—';
                document.getElementById('sub_stat_paused').textContent = s.paused ?? '—';
                document.getElementById('sub_stat_cancelled').textContent = s.cancelled ?? '—';
            })
            .catch(() => {});
    }

    /* ══════ LIST ══════ */

    function loadSubscriptions() {
        const loading = document.getElementById('sub_loading');
        const empty = document.getElementById('sub_empty');
        const container = document.getElementById('sub_container');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams();
        const search = document.getElementById('subSearch')?.value;
        const mode = document.getElementById('subModeFilter')?.value;
        const status = document.getElementById('subStatusFilter')?.value;
        if (search) params.set('search', search);
        if (mode) params.set('mode', mode);
        if (status) params.set('status', status);

        fetch(`/admin/company2/${subCompanyId}/subscriptions?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                subList = data.data || [];

                if (!subList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderSubscriptions();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load subscriptions');
            });
    }

    function renderSubscriptions() {
        const body = document.getElementById('sub_body');
        if (!body) return;
        body.innerHTML = '';

        subList.forEach(s => {
            const customerLabel = s.customer
                ? (s.customer.name || s.customer.email)
                : '—';

            const nextBilling = s.cancelled_at
                ? '<span class="text-muted">—</span>'
                : (s.next_billing_at || '—');

            let actions = '';
            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="viewSubscription(${s.id})" title="View" style="width:28px;height:28px;"><i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;

            if (['active','trialing'].includes(s.status)) {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="pauseSubscription(${s.id})" title="Pause" style="width:28px;height:28px;"><i class="ki-duotone ki-package fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>`;
            }

            if (s.status === 'paused') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="resumeSubscription(${s.id})" title="Resume" style="width:28px;height:28px;"><i class="ki-duotone ki-arrows-circle fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;
            }

            if (['active','trialing','past_due'].includes(s.status)) {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="billSubscriptionNow(${s.id})" title="Bill now" style="width:28px;height:28px;"><i class="ki-duotone ki-dollar fs-4 text-info"><span class="path1"></span><span class="path2"></span></i></button>`;
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light" onclick="cancelSubscription(${s.id})" title="Cancel" style="width:28px;height:28px;"><i class="ki-duotone ki-cross-circle fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i></button>`;
            }

            if (s.status === 'cancelled') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteSubscription(${s.id}, '${h(s.public_id)}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;
            }

            const statusExtra = s.is_cancelling
                ? '<div class="text-warning fs-8 mt-1">Ends ' + h(s.current_period_end) + '</div>'
                : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(s.description || s.public_id)}</div>
                    <div class="text-muted fs-8 font-monospace">${h(s.public_id)}</div>
                </td>
                <td class="d-none d-md-table-cell">${h(customerLabel)}</td>
                <td class="d-none d-md-table-cell">
                    <span class="badge badge-light-dark">${h(s.interval_label)}</span>
                </td>
                <td class="d-none d-lg-table-cell">
                    <span class="badge badge-light-${s.mode_badge.tone}">${h(s.mode_badge.label)}</span>
                </td>
                <td class="text-muted fs-7">${h(nextBilling)}</td>
                <td>
                    <span class="badge badge-light-${s.status_badge.tone}">${h(s.status_badge.label)}</span>
                    ${statusExtra}
                </td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">${actions}</div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    /* ══════ FILTERS ══════ */

    document.getElementById('subSearch')?.addEventListener('input', function () {
        clearTimeout(subSearchTimeout);
        subSearchTimeout = setTimeout(loadSubscriptions, 400);
    });

    document.getElementById('subModeFilter')?.addEventListener('change', loadSubscriptions);
    document.getElementById('subStatusFilter')?.addEventListener('change', loadSubscriptions);

    /* ══════ EDITOR ══════ */

    window.openAddSubscription = function () {
        document.getElementById('sub_editor_title').textContent = 'New Subscription';
        document.getElementById('sub_editor_subtitle').textContent = 'Not yet saved';
        document.getElementById('sub_id').value = '';
        document.getElementById('sub_company_id').value = subCompanyId;
        document.getElementById('subscriptionForm').reset();
        document.getElementById('sub_lines_body').innerHTML = '';

        document.getElementById('sub_billing_interval_count').value = 1;
        document.getElementById('sub_billing_interval').value = 'month';
        document.getElementById('sub_collection_method').value = 'charge_automatically';

        addBlankSubscriptionLine();

        loadSubCustomers();
        loadSubTaxRates();

        new bootstrap.Modal(document.getElementById('kt_modal_subscription_editor')).show();
    };

    window.addBlankSubscriptionLine = function () {
        addSubscriptionLine({
            name: '',
            unit_amount: 0,
            quantity: 1,
            tax_rate_id: null,
        });
    };

    function addSubscriptionLine(item) {
        const body = document.getElementById('sub_lines_body');
        if (!body) return;

        const currency = document.getElementById('sub_currency')?.value || 'USD';
        const unitAmount = item.unit_amount != null ? fromMinor(item.unit_amount, currency) : '';

        const taxOptions = subTaxRates.map(t => {
            const sel = (item.tax_rate_id == t.id) ? 'selected' : '';
            return `<option value="${t.id}" data-percentage="${t.percentage}" ${sel}>${h(t.label)}</option>`;
        }).join('');

        const row = document.createElement('tr');
        row.className = 'sub-line';
        row.innerHTML = `
            <td>
                <input type="text" class="form-control form-control-sm sub-line-name" value="${h(item.name || '')}" placeholder="Item name" />
                <input type="hidden" class="sub-line-product-id" value="${item.product_id || ''}" />
                <input type="hidden" class="sub-line-price-id" value="${item.price_id || ''}" />
            </td>
            <td><input type="number" min="1" class="form-control form-control-sm sub-line-qty" value="${item.quantity || 1}" /></td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm sub-line-unit" value="${unitAmount}" /></td>
            <td>
                <select class="form-select form-select-sm sub-line-tax">
                    <option value="">No tax</option>
                    ${taxOptions}
                </select>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-light-danger" onclick="removeSubscriptionLine(this)" title="Remove" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-trash fs-5 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            </td>
        `;
        body.appendChild(row);
    }

    window.removeSubscriptionLine = function (btn) {
        btn.closest('tr')?.remove();
    };

    /* ══════ SUBMIT ══════ */

    document.getElementById('subscriptionForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('subSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('sub_id').value;
        const companyId = document.getElementById('sub_company_id').value;
        const currency = (document.getElementById('sub_currency').value || 'USD').toUpperCase();

        // Read line items
        const items = [];
        document.querySelectorAll('#sub_lines_body .sub-line').forEach(row => {
            const name = row.querySelector('.sub-line-name')?.value?.trim();
            if (!name) return;

            const qty = parseInt(row.querySelector('.sub-line-qty')?.value) || 1;
            const unitMajor = parseFloat(row.querySelector('.sub-line-unit')?.value) || 0;
            const unitMinor = toMinor(unitMajor, currency);

            const taxSel = row.querySelector('.sub-line-tax');
            const taxRateId = taxSel?.value || null;

            items.push({
                product_id: row.querySelector('.sub-line-product-id')?.value || null,
                price_id: row.querySelector('.sub-line-price-id')?.value || null,
                name,
                unit_amount: unitMinor,
                currency,
                quantity: qty,
                tax_rate_id: taxRateId ? parseInt(taxRateId) : null,
            });
        });

        if (!items.length) {
            window.showToast('warning', 'Add at least one line item.');
            window.hideButtonSpinner(btn);
            return;
        }

        const payload = {
            mode: document.getElementById('sub_mode').value,
            customer_id: parseInt(document.getElementById('sub_customer_id').value),
            description: document.getElementById('sub_description').value || null,
            currency,
            collection_method: document.getElementById('sub_collection_method').value,
            billing_interval: document.getElementById('sub_billing_interval').value,
            billing_interval_count: parseInt(document.getElementById('sub_billing_interval_count').value) || 1,
            trial_period_days: document.getElementById('sub_trial_period_days').value || null,
            items,
        };

        const url = id
            ? `/admin/company2/subscriptions/${id}`
            : `/admin/company2/subscriptions/${companyId}`;
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
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_subscription_editor'))?.hide();
                loadSubscriptionStats();
                loadSubscriptions();
            } else {
                window.showToast('error', d.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ══════ ACTIONS ══════ */

    window.pauseSubscription = function (id) {
        if (!confirm('Pause this subscription? Billing will stop until resumed.')) return;

        fetch(`/admin/company2/subscriptions/${id}/pause`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadSubscriptionStats();
                loadSubscriptions();
            } else window.showToast('error', d.message);
        });
    };

    window.resumeSubscription = function (id) {
        fetch(`/admin/company2/subscriptions/${id}/resume`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadSubscriptionStats();
                loadSubscriptions();
            } else window.showToast('error', d.message);
        });
    };

    window.cancelSubscription = function (id) {
        const atEnd = confirm(
            'Cancel at period end?\n\n' +
            'Click OK — cancels at the end of the current period (recommended).\n' +
            'Click Cancel — cancels immediately.'
        );

        const reason = prompt('Cancellation reason (optional):') || null;

        fetch(`/admin/company2/subscriptions/${id}/cancel`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ at_period_end: atEnd ? 1 : 0, reason })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadSubscriptionStats();
                loadSubscriptions();
            } else window.showToast('error', d.message);
        });
    };

    window.billSubscriptionNow = function (id) {
        if (!confirm('Generate an invoice for this subscription now?\n\nThe billing period will advance.')) return;

        fetch(`/admin/company2/subscriptions/${id}/bill-now`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadSubscriptionStats();
                loadSubscriptions();
            } else window.showToast('error', d.message);
        });
    };

    window.deleteSubscription = function (id, label) {
        if (!confirm(`Delete "${label}"? Only cancelled/expired subscriptions can be deleted.`)) return;

        fetch(`/admin/company2/subscriptions/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadSubscriptionStats();
                loadSubscriptions();
            } else window.showToast('error', d.message);
        });
    };

    window.viewSubscription = function (id) {
        fetch(`/admin/company2/subscriptions/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const s = d.data;

                const itemsHtml = (s.items || []).map(i => `
                    <tr>
                        <td>${h(i.name)}</td>
                        <td>${i.quantity}</td>
                        <td class="text-end">${money(i.unit_amount, i.currency)}</td>
                        <td class="text-end">${money(i.unit_amount * i.quantity, i.currency)}</td>
                    </tr>
                `).join('');

                const body = `
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><strong>Status:</strong> <span class="badge badge-light-${s.status_badge.tone}">${h(s.status_badge.label)}</span></div>
                        <div class="col-md-6"><strong>Interval:</strong> ${h(s.interval_label)}</div>
                        <div class="col-md-6"><strong>Current period:</strong> ${h(s.current_period_start)} → ${h(s.current_period_end)}</div>
                        <div class="col-md-6"><strong>Next billing:</strong> ${h(s.next_billing_at || '—')}</div>
                        <div class="col-md-6"><strong>Invoices generated:</strong> ${s.invoices_generated}</div>
                        <div class="col-md-6"><strong>Failed attempts:</strong> ${s.failed_payment_attempts}</div>
                    </div>

                    <h4 class="fw-bold mb-3">Line Items</h4>
                    <table class="table table-row-dashed fs-7">
                        <thead><tr class="text-muted fw-bold text-uppercase"><th>Name</th><th>Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr></thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                `;

                // Reuse the same editor modal for the detail view? Simpler: use a bootstrap alert-style modal.
                // For now, open a lightweight modal via JS alert — replace with a proper modal later.
                const win = window.open('', '_blank', 'width=600,height=500');
                if (win) {
                    win.document.write(`<html><head><title>Subscription ${h(s.public_id)}</title>
                        <style>body{font-family:sans-serif;padding:20px;font-size:14px;}
                        table{width:100%;border-collapse:collapse;margin-top:10px;}
                        th,td{padding:6px;border-bottom:1px solid #eee;text-align:left;}
                        .badge{padding:2px 8px;border-radius:4px;background:#eee;}</style></head>
                        <body><h2>${h(s.description || s.public_id)}</h2>${body}</body></html>`);
                    win.document.close();
                }
            })
            .catch(() => window.showToast('error', 'Failed to load subscription'));
    };

})();
</script>