@extends('layouts.admin')

@section('title', 'Payment Links')
@section('page_title', 'Payment Links')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Payments</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Payment Links</li>
@endsection

@section('content')
    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100">
                    <div class="position-relative w-100" style="max-width:280px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid ps-12 w-100" placeholder="Search companies" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading companies...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Company</th>
                                <th class="min-w-150px d-none d-md-table-cell">Country</th>
                                <th class="min-w-120px d-none d-lg-table-cell">Currency</th>
                                <th class="min-w-120px">Links</th>
                                <th class="min-w-120px d-none d-md-table-cell">Status</th>
                                <th class="text-end min-w-180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="companiesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No companies found.</p>
            </div>

            <div id="paginationContainer" class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-5 d-none">
                <div id="paginationInfo" class="text-muted fs-7 text-center text-sm-start"></div>
                <nav><ul class="pagination m-0 justify-content-center justify-content-sm-end" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Payment Links modal — will be populated with the actual link list per company --}}
    @include('company2.payment-links._payment_links_modal')
    @include('company2.payment-links._subscriptions_modals')
@endsection




@push('scripts')
@include('company2.payment-links._subscriptions_script')
<script>
/* ═══════════════════════════════════════════════════════
   COMPANIES LIST (for the company2 page)
   ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    let currentPage = 1;
    let currentSearch = '';
    let searchTimeout = null;

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function loadCompanies() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        const params = new URLSearchParams({ page: currentPage, per_page: 20 });
        if (currentSearch) params.set('search', currentSearch);

        fetch(`{{ route('admin.company2.data') }}?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (!data.data.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load companies');
            });
    }

    function renderTable(companies) {
        const tbody = document.getElementById('companiesTableBody');
        tbody.innerHTML = '';

        companies.forEach(c => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3" style="background:${c.brand_color || '#f1f1f4'};">
                        <span class="text-white fw-bold">${escapeHtml((c.name || '?').charAt(0).toUpperCase())}</span>
                    </div>
                    <div>
                        <div class="fw-bold text-gray-800">${escapeHtml(c.name)}</div>
                        <div class="text-muted fs-7">${escapeHtml(c.public_id)}</div>
                    </div>
                </div>
            `;

            const countryCell = row.insertCell(1);
            countryCell.className = 'd-none d-md-table-cell';
            countryCell.innerHTML = c.country
                ? `${c.country.flag_emoji ?? ''} ${escapeHtml(c.country.name)}`
                : '<span class="text-muted">—</span>';

            const currencyCell = row.insertCell(2);
            currencyCell.className = 'd-none d-lg-table-cell';
            currencyCell.innerHTML = `<span class="badge badge-light-primary">${escapeHtml(c.default_currency || '—')}</span>`;

            row.insertCell(3).innerHTML = `<span class="badge badge-light-info">${c.payment_links_count || 0}</span>`;

            const statusCell = row.insertCell(4);
            statusCell.className = 'd-none d-md-table-cell';
            statusCell.innerHTML = `<span class="badge badge-light-${c.status_badge.tone}">${escapeHtml(c.status_badge.label)}</span>`;

            const actionCell = row.insertCell(5);
            actionCell.className = 'text-end';

            actionCell.innerHTML = `
                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light"
                        onclick="openPaymentLinks(${c.id}, '${escapeHtml(c.name).replace(/'/g, "\\'")}')"
                        title="Payment Links"
                        style="width:32px;height:32px;">
                        <i class="ki-duotone ki-abstract-26 fs-3 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light"
                        onclick="openSubscriptions(${c.id}, '${escapeHtml(c.name).replace(/'/g, "\\'")}')"
                        title="Subscriptions"
                        style="width:32px;height:32px;">
                        <i class="ki-duotone ki-abstract-28 fs-3 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                </div>
            `;

        });
    }

    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); changePage(page); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
        let start = Math.max(1, data.current_page - 2);
        let end = Math.min(data.last_page, data.current_page + 2);
        if (start > 1) addPage(1, '1');
        if (start > 2) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = '<span class="page-link">...</span>';
            el.appendChild(dots);
        }
        for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
        if (end < data.last_page - 1) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = '<span class="page-link">...</span>';
            el.appendChild(dots);
        }
        if (end < data.last_page) addPage(data.last_page, data.last_page);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.changePage = function (page) {
        if (page > 0 && page !== currentPage) {
            currentPage = page;
            loadCompanies();
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        loadCompanies();

        const searchInput = document.getElementById('searchInput');
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCompanies();
            }, 400);
        });
    });

})();
</script>

<script>
/* ═══════════════════════════════════════════════════════
   PAYMENT LINKS MODAL
   ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    let plCompanyId = null;
    let plLinks = [];
    let plSearchTimeout = null;

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

    window.openPaymentLinks = function (companyId, companyName) {
        plCompanyId = companyId;
        document.getElementById('pl_company_name').textContent = companyName || 'Company';

        document.getElementById('plSearch').value = '';
        document.getElementById('plModeFilter').value = '';
        document.getElementById('plStatusFilter').value = '';

        loadStats();
        loadPaymentLinks();

        new bootstrap.Modal(document.getElementById('kt_modal_payment_links')).show();
    };

    /* ══════ STATS ══════ */

    function loadStats() {
        fetch(`/admin/company2/${plCompanyId}/payment-links/stats`)
            .then(r => r.json())
            .then(s => {
                document.getElementById('pl_stat_total').textContent = s.total ?? '—';
                document.getElementById('pl_stat_active').textContent = s.active ?? '—';
                document.getElementById('pl_stat_expired').textContent = s.expired ?? '—';
                document.getElementById('pl_stat_completed').textContent = s.completed ?? '—';
                document.getElementById('pl_stat_collected').textContent = money(s.total_collected || 0, 'USD');
                document.getElementById('pl_stat_payments').textContent = s.total_payments ?? '—';
            })
            .catch(() => {});
    }

    /* ══════ LIST ══════ */

    function loadPaymentLinks() {
        const loading = document.getElementById('pl_loading');
        const empty = document.getElementById('pl_empty');
        const container = document.getElementById('pl_container');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams();
        const search = document.getElementById('plSearch')?.value;
        const mode = document.getElementById('plModeFilter')?.value;
        const status = document.getElementById('plStatusFilter')?.value;
        if (search) params.set('search', search);
        if (mode) params.set('mode', mode);
        if (status) params.set('status', status);

        fetch(`/admin/company2/${plCompanyId}/payment-links?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                plLinks = data.data || [];

                if (!plLinks.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderLinks();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load payment links');
            });
    }

    function renderLinks() {
        const body = document.getElementById('pl_body');
        if (!body) return;
        body.innerHTML = '';

        plLinks.forEach(l => {
            const amountDisplay = l.amount_type === 'customer_chooses'
                ? '<span class="badge badge-light-info">Customer chooses</span>'
                : (l.amount_display || '—');

            const usageDisplay = l.max_payments
                ? `${l.payments_count} / ${l.max_payments}`
                : `${l.payments_count}`;

            let actions = '';
            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="copyPaymentLinkUrl(${l.id})" title="Copy URL" style="width:28px;height:28px;"><i class="ki-duotone ki-copy fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;
            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editPaymentLink(${l.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;

            if (l.status === 'active') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="togglePaymentLink(${l.id})" title="Deactivate" style="width:28px;height:28px;"><i class="ki-duotone ki-minus-circle fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>`;
            } else {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="togglePaymentLink(${l.id})" title="Activate" style="width:28px;height:28px;"><i class="ki-duotone ki-check fs-4"></i></button>`;
            }

            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="duplicatePaymentLink(${l.id})" title="Duplicate" style="width:28px;height:28px;"><i class="ki-duotone ki-copy fs-4 text-info"><span class="path1"></span><span class="path2"></span></i></button>`;
            actions += `<button type="button" class="btn btn-sm btn-icon btn-light" onclick="deletePaymentLink(${l.id}, '${h(l.title).replace(/'/g, "\\'")}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(l.title)}</div>
                    <div class="text-muted fs-8 font-monospace">${h(l.public_id)}</div>
                </td>
                <td class="d-none d-md-table-cell">${amountDisplay}</td>
                <td class="d-none d-lg-table-cell">
                    <span class="badge badge-light-${l.mode_badge.tone}">${h(l.mode_badge.label)}</span>
                </td>
                <td class="d-none d-md-table-cell">
                    <span class="badge badge-light-dark">${usageDisplay}</span>
                    ${l.usage_type === 'single_use' ? '<div class="text-muted fs-8">Single use</div>' : ''}
                </td>
                <td><div class="fw-bold">${h(l.amount_collected_display || '—')}</div></td>
                <td>
                    <span class="badge badge-light-${l.status_badge.tone}">${h(l.status_badge.label)}</span>
                    ${l.is_expired ? '<div class="text-danger fs-8 mt-1">Expired</div>' : ''}
                </td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">${actions}</div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    /* ══════ FILTERS ══════ */

    document.getElementById('plSearch')?.addEventListener('input', function () {
        clearTimeout(plSearchTimeout);
        plSearchTimeout = setTimeout(loadPaymentLinks, 400);
    });

    document.getElementById('plModeFilter')?.addEventListener('change', loadPaymentLinks);
    document.getElementById('plStatusFilter')?.addEventListener('change', loadPaymentLinks);

    /* ══════ EDITOR ══════ */

    window.openAddPaymentLink = function () {
        document.getElementById('pl_editor_title').textContent = 'New Payment Link';
        document.getElementById('pl_editor_subtitle').textContent = 'Not yet saved';
        document.getElementById('pl_id').value = '';
        document.getElementById('pl_company_id').value = plCompanyId;
        document.getElementById('paymentLinkForm').reset();

        document.getElementById('pl_amount_type').value = 'fixed';
        document.getElementById('pl_usage_type').value = 'multi_use';
        document.getElementById('pl_after_completion').value = 'hosted_confirmation';
        document.getElementById('pl_collect_customer_name').checked = true;
        document.getElementById('pl_collect_email').checked = true;
        document.getElementById('pl_send_receipt').checked = true;
        document.getElementById('pl_currency').value = '';

        togglePaymentLinkAmountFields();
        togglePaymentLinkUsageFields();
        togglePaymentLinkAfterCompletion();

        new bootstrap.Modal(document.getElementById('kt_modal_payment_link_editor')).show();
    };

    window.editPaymentLink = function (id) {
        fetch(`/admin/company2/payment-links/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const l = d.data;

                document.getElementById('pl_editor_title').textContent = 'Edit ' + l.title;
                document.getElementById('pl_editor_subtitle').textContent = l.public_id;

                document.getElementById('pl_id').value = l.id;
                document.getElementById('pl_company_id').value = l.company_id;
                document.getElementById('pl_title').value = l.title || '';
                document.getElementById('pl_description').value = l.description || '';
                document.getElementById('pl_mode').value = l.mode || 'test';
                document.getElementById('pl_amount_type').value = l.amount_type || 'fixed';
                document.getElementById('pl_currency').value = l.currency || '';

                const currency = l.currency || 'USD';
                document.getElementById('pl_amount').value = l.amount ? fromMinor(l.amount, currency) : '';
                document.getElementById('pl_minimum_amount').value = l.minimum_amount ? fromMinor(l.minimum_amount, currency) : '';
                document.getElementById('pl_maximum_amount').value = l.maximum_amount ? fromMinor(l.maximum_amount, currency) : '';

                document.getElementById('pl_collect_customer_name').checked = !!l.collect_customer_name;
                document.getElementById('pl_collect_email').checked = !!l.collect_email;
                document.getElementById('pl_collect_phone').checked = !!l.collect_phone;
                document.getElementById('pl_collect_billing_address').checked = !!l.collect_billing_address;
                document.getElementById('pl_collect_shipping_address').checked = !!l.collect_shipping_address;

                document.getElementById('pl_usage_type').value = l.usage_type || 'multi_use';
                document.getElementById('pl_max_payments').value = l.max_payments || '';
                document.getElementById('pl_expires_at').value = l.expires_at ? l.expires_at.substring(0, 16) : '';

                document.getElementById('pl_after_completion').value = l.after_completion || 'hosted_confirmation';
                document.getElementById('pl_success_url').value = l.success_url || '';
                document.getElementById('pl_success_message').value = l.success_message || '';
                document.getElementById('pl_send_receipt').checked = !!l.send_receipt;

                document.getElementById('pl_reference_prefix').value = l.reference_prefix || '';
                document.getElementById('pl_statement_descriptor').value = l.statement_descriptor || '';

                togglePaymentLinkAmountFields();
                togglePaymentLinkUsageFields();
                togglePaymentLinkAfterCompletion();

                new bootstrap.Modal(document.getElementById('kt_modal_payment_link_editor')).show();
            })
            .catch(() => window.showToast('error', 'Failed to load payment link'));
    };

    window.togglePaymentLinkAmountFields = function () {
        const type = document.getElementById('pl_amount_type')?.value;
        document.getElementById('pl_fixed_amount_row').classList.toggle('d-none', type !== 'fixed');
        document.getElementById('pl_customer_chooses_rows').classList.toggle('d-none', type !== 'customer_chooses');
    };

    window.togglePaymentLinkUsageFields = function () {
        const type = document.getElementById('pl_usage_type')?.value;
        document.getElementById('pl_max_payments_row').classList.toggle('d-none', type !== 'multi_use');
    };

    window.togglePaymentLinkAfterCompletion = function () {
        const val = document.getElementById('pl_after_completion')?.value;
        document.getElementById('pl_success_url_row').classList.toggle('d-none', val !== 'redirect');
    };

    /* ══════ SUBMIT ══════ */

    document.getElementById('paymentLinkForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('plSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('pl_id').value;
        const companyId = document.getElementById('pl_company_id').value;
        const currency = (document.getElementById('pl_currency').value || 'USD').toUpperCase();
        const amountType = document.getElementById('pl_amount_type').value;

        const payload = {
            company_id: parseInt(companyId),
            mode: document.getElementById('pl_mode').value,
            title: document.getElementById('pl_title').value,
            description: document.getElementById('pl_description').value || null,
            amount_type: amountType,
            currency,
            amount: amountType === 'fixed' ? toMinor(document.getElementById('pl_amount').value, currency) : null,
            minimum_amount: amountType === 'customer_chooses' ? toMinor(document.getElementById('pl_minimum_amount').value, currency) : null,
            maximum_amount: amountType === 'customer_chooses' ? toMinor(document.getElementById('pl_maximum_amount').value, currency) : null,
            collect_customer_name: document.getElementById('pl_collect_customer_name').checked ? 1 : 0,
            collect_email: document.getElementById('pl_collect_email').checked ? 1 : 0,
            collect_phone: document.getElementById('pl_collect_phone').checked ? 1 : 0,
            collect_billing_address: document.getElementById('pl_collect_billing_address').checked ? 1 : 0,
            collect_shipping_address: document.getElementById('pl_collect_shipping_address').checked ? 1 : 0,
            usage_type: document.getElementById('pl_usage_type').value,
            max_payments: document.getElementById('pl_max_payments').value || null,
            expires_at: document.getElementById('pl_expires_at').value || null,
            after_completion: document.getElementById('pl_after_completion').value,
            success_url: document.getElementById('pl_success_url').value || null,
            success_message: document.getElementById('pl_success_message').value || null,
            send_receipt: document.getElementById('pl_send_receipt').checked ? 1 : 0,
            reference_prefix: document.getElementById('pl_reference_prefix').value || null,
            statement_descriptor: document.getElementById('pl_statement_descriptor').value || null,
        };

        const url = id
            ? `/admin/company2/payment-links/${id}`
            : `/admin/company2/payment-links/${companyId}`;
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
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_payment_link_editor'))?.hide();
                loadStats();
                loadPaymentLinks();
            } else {
                window.showToast('error', d.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ══════ ACTIONS ══════ */

    window.togglePaymentLink = function (id) {
        fetch(`/admin/company2/payment-links/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadPaymentLinks();
            } else window.showToast('error', d.message);
        });
    };

    window.duplicatePaymentLink = function (id) {
        if (!confirm('Duplicate this link? The copy will be inactive.')) return;
        fetch(`/admin/company2/payment-links/${id}/duplicate`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadPaymentLinks();
            } else window.showToast('error', d.message);
        });
    };

    window.deletePaymentLink = function (id, title) {
        if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
        fetch(`/admin/company2/payment-links/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadPaymentLinks();
            } else window.showToast('error', d.message);
        });
    };

    window.copyPaymentLinkUrl = function (id) {
        const link = plLinks.find(x => x.id === id);
        if (!link) return;
        const url = link.public_url || (window.location.origin + '/pay/' + link.public_id);
        navigator.clipboard.writeText(url).then(() => {
            window.showToast('success', 'Link copied to clipboard');
        }).catch(() => {
            prompt('Copy this URL:', url);
        });
    };

})();
</script>
@endpush