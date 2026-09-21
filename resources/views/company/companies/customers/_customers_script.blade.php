<script>
(function () {
    'use strict';

    let customersCompanyId = null;
    let customersList = [];
    let currentCustomerPage = 1;
    let searchTimeout = null;

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function qs(s) {
        return String(s || '').replace(/'/g, "\\'");
    }

    /* ═══════════════ OPEN ═══════════════ */

    window.openCustomers = function (companyId, companyName) {
        if (!companyId) {
            window.showToast('error', 'Missing company ID');
            return;
        }

        customersCompanyId = companyId;
        const nameEl = document.getElementById('customers_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        currentCustomerPage = 1;
        loadCustomers();

        new bootstrap.Modal(document.getElementById('kt_modal_customers')).show();
    };

    /* ═══════════════ LIST ═══════════════ */

    function loadCustomers() {
        const loading = document.getElementById('cust_loading');
        const empty = document.getElementById('cust_empty');
        const container = document.getElementById('cust_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams({
            page: currentCustomerPage,
            per_page: 20,
        });

        const search = document.getElementById('custSearch')?.value;
        const mode = document.getElementById('custModeFilter')?.value;
        const status = document.getElementById('custStatusFilter')?.value;
        if (search) params.set('search', search);
        if (mode) params.set('mode', mode);
        if (status) params.set('status', status);

        fetch(`/admin/companies/${customersCompanyId}/customers?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                customersList = data.data || [];

                if (!customersList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderCustomers();
                renderCustomersPagination(data);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load customers');
            });
    }

    function renderCustomers() {
        const body = document.getElementById('cust_body');
        if (!body) return;
        body.innerHTML = '';

        customersList.forEach(c => {
            const initials = c.initials || '?';
            const name = c.display_name || c.name || '—';

            const contact = [
                c.email ? `<div>${h(c.email)}</div>` : '',
                c.phone ? `<div class="text-muted fs-8">${h(c.phone)}</div>` : '',
            ].join('') || '<span class="text-muted">—</span>';

            const ltv = c.lifetime_value
                ? `<span class="fw-bold">${formatMoney(c.lifetime_value, c.preferred_currency || 'USD')}</span>`
                : '<span class="text-muted">—</span>';

            const lastPaid = c.last_paid_at
                ? `<div class="text-muted fs-8">Last: ${h(c.last_paid_at)}</div>`
                : '';

            const actions = `
                <div class="d-flex flex-wrap justify-content-end gap-2" style="max-width:72px;">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewCustomer(${c.id})" title="View" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editCustomer(${c.id})" title="Edit" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="toggleCustomerBlock(${c.id}, ${c.is_blocked})" title="${c.is_blocked ? 'Unblock' : 'Block'}" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-${c.is_blocked ? 'check' : 'disconnect'} fs-4 ${c.is_blocked ? 'text-success' : 'text-warning'}"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteCustomer(${c.id}, '${qs(name)}')" title="Delete" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </div>
            `;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px symbol-circle me-3" style="background:#f1f1f4;">
                            <span class="fw-bold text-gray-700">${h(initials)}</span>
                        </div>
                        <div>
                            <div class="fw-bold">${h(name)}</div>
                            <div class="text-muted fs-8">${h(c.public_id)}</div>
                        </div>
                    </div>
                </td>
                <td>${contact}</td>
                <td><span class="badge badge-light-${c.mode_badge.tone}">${h(c.mode_badge.label)}</span></td>
                <td>${ltv}${lastPaid}</td>
                <td>
                    <div>${c.successful_payments_count || 0} successful</div>
                    ${c.disputed_payments_count ? `<div class="text-danger fs-8">${c.disputed_payments_count} disputed</div>` : ''}
                </td>
                <td><span class="badge badge-light-${c.status_badge.tone}">${h(c.status_badge.label)}</span></td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    function renderCustomersPagination(data) {
        const info = document.getElementById('cust_info');
        const el = document.getElementById('cust_pager');
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
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); changeCustomerPage(page); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    function changeCustomerPage(page) {
        currentCustomerPage = page;
        loadCustomers();
    }

    function formatMoney(minor, currency) {
        const zeroDecimal = ['UGX', 'RWF', 'BIF', 'XOF', 'XAF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'XPF'];
        const amount = zeroDecimal.includes(currency) ? minor : minor / 100;
        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency,
                minimumFractionDigits: zeroDecimal.includes(currency) ? 0 : 2,
            }).format(amount);
        } catch {
            return `${currency} ${amount.toLocaleString()}`;
        }
    }

    /* ═══════════════ ADD / EDIT ═══════════════ */

    window.openAddCustomer = function () {
        document.getElementById('cust_modal_title').textContent = 'Add Customer';
        document.getElementById('cust_id').value = '';
        document.getElementById('cust_company_id').value = customersCompanyId;
        document.getElementById('customerForm').reset();
        new bootstrap.Modal(document.getElementById('kt_modal_add_customer')).show();
    };

    window.editCustomer = function (id) {
        fetch(`/admin/customers/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const c = d.data;

                document.getElementById('cust_modal_title').textContent = 'Edit Customer';
                document.getElementById('cust_id').value = c.id;
                document.getElementById('cust_company_id').value = c.company_id;
                document.getElementById('cust_name').value = c.name || '';
                document.getElementById('cust_mode').value = c.mode || 'test';
                document.getElementById('cust_email').value = c.email || '';
                document.getElementById('cust_phone').value = c.phone || '';
                document.getElementById('cust_reference').value = c.reference || '';
                document.getElementById('cust_country_code').value = c.country_code || '';
                document.getElementById('cust_preferred_currency').value = c.preferred_currency || '';
                document.getElementById('cust_description').value = c.description || '';
                document.getElementById('cust_tax_exempt').checked = !!c.tax_exempt;
                document.getElementById('cust_tax_id').value = c.tax_id || '';

                new bootstrap.Modal(document.getElementById('kt_modal_add_customer')).show();
            });
    };

    document.getElementById('customerForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('custSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('cust_id').value;
        const companyId = document.getElementById('cust_company_id').value;

        const payload = {
            name: document.getElementById('cust_name').value || null,
            mode: document.getElementById('cust_mode').value,
            email: document.getElementById('cust_email').value || null,
            phone: document.getElementById('cust_phone').value || null,
            reference: document.getElementById('cust_reference').value || null,
            country_code: document.getElementById('cust_country_code').value || null,
            preferred_currency: document.getElementById('cust_preferred_currency').value || null,
            description: document.getElementById('cust_description').value || null,
            tax_exempt: document.getElementById('cust_tax_exempt').checked ? 1 : 0,
            tax_id: document.getElementById('cust_tax_id').value || null,
        };

        const url = id
            ? `/admin/customers/${id}`
            : `/admin/companies/${companyId}/customers`;

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
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_customer'))?.hide();
                loadCustomers();
            } else {
                window.showToast('error', d.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ═══════════════ VIEW ═══════════════ */

    window.viewCustomer = function (id) {
        const body = document.getElementById('cust_detail_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_customer_detail')).show();

        fetch(`/admin/customers/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const c = d.data;

                document.getElementById('cust_detail_name').textContent = c.display_name || c.name || 'Customer';
                document.getElementById('cust_detail_public_id').textContent = c.public_id;

                const addressHtml = c.addresses.length
                    ? c.addresses.map(a => `
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge badge-light-dark fs-8">${h(a.type)}</span>
                                ${a.is_default ? '<span class="badge badge-light-primary fs-8">Default</span>' : ''}
                            </div>
                            <div class="mt-2 fs-7">${h(a.one_line)}</div>
                        </div>
                    `).join('')
                    : '<span class="text-muted">No addresses on file.</span>';

                const pmHtml = c.payment_methods.length
                    ? c.payment_methods.map(pm => `
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div>
                                <div class="fw-semibold">${h(pm.display_label)}</div>
                                <div class="text-muted fs-8">${h(pm.provider || 'tokenised')}</div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                ${pm.is_default ? '<span class="badge badge-light-primary">Default</span>' : ''}
                                <span class="badge badge-light-${pm.status_badge.tone}">${h(pm.status_badge.label)}</span>
                            </div>
                        </div>
                    `).join('')
                    : '<span class="text-muted">No payment methods on file.</span>';

                body.innerHTML = `
                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <div class="border border-gray-300 border-dashed rounded p-4">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Contact</div>
                                <div class="mb-1"><strong>Email:</strong> ${h(c.email || '—')}</div>
                                <div class="mb-1"><strong>Phone:</strong> ${h(c.phone || '—')}</div>
                                <div><strong>Reference:</strong> ${h(c.reference || '—')}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-gray-300 border-dashed rounded p-4">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Metrics</div>
                                <div class="mb-1"><strong>LTV:</strong> ${formatMoney(c.lifetime_value || 0, c.preferred_currency || 'USD')}</div>
                                <div class="mb-1"><strong>Successful:</strong> ${c.successful_payments_count || 0}</div>
                                <div><strong>Disputed:</strong> ${c.disputed_payments_count || 0}</div>
                            </div>
                        </div>
                    </div>

                    <h4 class="fw-bold mb-3">Addresses</h4>
                    <div class="mb-7">${addressHtml}</div>

                    <h4 class="fw-bold mb-3">Payment Methods</h4>
                    <div class="mb-7">${pmHtml}</div>

                    <h4 class="fw-bold mb-3">Description</h4>
                    <div class="text-muted">${h(c.description || '—')}</div>
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load customer</div>';
            });
    };

    /* ═══════════════ ACTIONS ═══════════════ */

    window.toggleCustomerBlock = function (id, isBlocked) {
        const message = isBlocked
            ? 'Unblock this customer? They will be able to make payments again.'
            : 'Block this customer? Future payments will be rejected.';
        if (!confirm(message)) return;

        const reason = !isBlocked ? prompt('Block reason (optional):') : null;

        fetch(`/admin/customers/${id}/toggle-block`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ blocked_reason: reason })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadCustomers();
            } else window.showToast('error', d.message);
        });
    };

    window.deleteCustomer = function (id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

        fetch(`/admin/customers/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadCustomers();
            } else window.showToast('error', d.message);
        });
    };

    /* ═══════════════ FILTERS ═══════════════ */

    document.getElementById('custSearch')?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentCustomerPage = 1;
            loadCustomers();
        }, 400);
    });

    document.getElementById('custModeFilter')?.addEventListener('change', () => {
        currentCustomerPage = 1;
        loadCustomers();
    });

    document.getElementById('custStatusFilter')?.addEventListener('change', () => {
        currentCustomerPage = 1;
        loadCustomers();
    });

})();
</script>