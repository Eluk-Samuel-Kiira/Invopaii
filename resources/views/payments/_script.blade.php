<script>
(function () {
    'use strict';

    let currentPage = 1;
    let currentSearch = '';
    let formOptions = { companies: [], providers: [], statuses: [], methods: [] };
    let currentPaymentId = null;
    let searchTimer = null;

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

    /* ══════ INIT ══════ */

    document.addEventListener('DOMContentLoaded', function () {
        loadFormOptions();

        const searchInput = document.getElementById('searchInput');
        searchInput?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPayments();
            }, 400);
        });

        ['companyFilter', 'statusFilter', 'modeFilter', 'methodFilter', 'providerFilter'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                currentPage = 1;
                loadPayments();
            });
        });

        loadStats();
        loadPayments();
    });

    function loadFormOptions() {
        fetch('{{ route("admin.payments.form-options") }}')
            .then(r => r.json())
            .then(opts => {
                formOptions = opts;

                const c = document.getElementById('companyFilter');
                c.innerHTML = '<option value="">All companies</option>' +
                    opts.companies.map(x => `<option value="${x.value}">${h(x.label)}</option>`).join('');

                const m = document.getElementById('methodFilter');
                m.innerHTML = '<option value="">All methods</option>' +
                    opts.methods.map(x => `<option value="${x.value}">${h(x.label)}</option>`).join('');

                const p = document.getElementById('providerFilter');
                p.innerHTML = '<option value="">All providers</option>' +
                    opts.providers.map(x => `<option value="${x.value}">${h(x.label)}</option>`).join('');
            })
            .catch(() => {});
    }

    function loadStats() {
        const params = new URLSearchParams();
        const c = document.getElementById('companyFilter')?.value;
        const mode = document.getElementById('modeFilter')?.value;
        if (c) params.set('company_id', c);
        if (mode) params.set('mode', mode);

        fetch('{{ route("admin.payments.stats") }}?' + params.toString())
            .then(r => r.json())
            .then(s => {
                document.getElementById('stat_total').textContent = s.total ?? '—';
                document.getElementById('stat_succeeded').textContent = s.succeeded ?? '—';
                document.getElementById('stat_failed').textContent = s.failed ?? '—';
                document.getElementById('stat_pending').textContent = s.pending ?? '—';
                document.getElementById('stat_refunded').textContent = s.refunded ?? '—';
                document.getElementById('stat_volume').textContent = money(s.volume_captured || 0, s.volume_currency || 'USD');
            })
            .catch(() => {});
    }

    function loadPayments() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        const params = new URLSearchParams({ page: currentPage, per_page: 20 });
        const s = document.getElementById('searchInput')?.value;
        const c = document.getElementById('companyFilter')?.value;
        const st = document.getElementById('statusFilter')?.value;
        const mode = document.getElementById('modeFilter')?.value;
        const m = document.getElementById('methodFilter')?.value;
        const p = document.getElementById('providerFilter')?.value;
        if (s) params.set('search', s);
        if (c) params.set('company_id', c);
        if (st) params.set('status', st);
        if (mode) params.set('mode', mode);
        if (m) params.set('payment_method_type', m);
        if (p) params.set('payment_provider_id', p);

        fetch('{{ route("admin.payments.data") }}?' + params.toString())
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (!data.data.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderPayments(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load payments');
            });
    }

    function renderPayments(payments) {
        const tbody = document.getElementById('paymentsTableBody');
        tbody.innerHTML = '';

        payments.forEach(p => {
            const row = tbody.insertRow();

            // Payment
            row.insertCell(0).innerHTML = `
                <div class="fw-bold font-monospace">${h(p.public_id)}</div>
                <div class="text-muted fs-8">
                    <span class="badge badge-light-${p.mode_badge.tone} fs-8">${h(p.mode_badge.label)}</span>
                    ${p.reference ? `<span class="ms-1">${h(p.reference)}</span>` : ''}
                </div>
            `;

            // Company
            const companyCell = row.insertCell(1);
            companyCell.className = 'd-none d-md-table-cell';
            companyCell.innerHTML = p.company ? `
                <div class="fw-bold">${h(p.company.name)}</div>
                <div class="text-muted fs-8 font-monospace">${h(p.company.public_id)}</div>
            ` : '<span class="text-muted">—</span>';

            // Amount
            row.insertCell(2).innerHTML = `
                <div class="fw-bold">${h(p.amount_display)}</div>
                ${p.amount_captured < p.amount && p.amount_captured > 0
                    ? `<div class="text-muted fs-8">Captured: ${h(p.amount_captured_display)}</div>`
                    : ''}
            `;

            // Method
            const methodCell = row.insertCell(3);
            methodCell.className = 'd-none d-lg-table-cell';
            methodCell.innerHTML = p.payment_method_type
                ? `<span class="badge badge-light-dark">${h(p.payment_method_type.replace(/_/g, ' '))}</span>`
                : '<span class="text-muted">—</span>';

            // Provider
            const providerCell = row.insertCell(4);
            providerCell.className = 'd-none d-lg-table-cell';
            providerCell.innerHTML = p.provider
                ? `<span class="badge badge-light-info">${h(p.provider.name)}</span>`
                : '<span class="text-muted">—</span>';

            // Status
            row.insertCell(5).innerHTML = `
                <span class="badge badge-light-${p.status_badge.tone}">${h(p.status_badge.label)}</span>
            `;

            // Attempts
            const attemptCell = row.insertCell(6);
            attemptCell.className = 'd-none d-xl-table-cell';
            attemptCell.innerHTML = `<span class="badge badge-light-dark">${p.attempt_count}</span>`;

            // Created
            const createdCell = row.insertCell(7);
            createdCell.className = 'd-none d-md-table-cell text-muted';
            createdCell.textContent = p.created_at || '—';

            // Actions
            const actionCell = row.insertCell(8);
            actionCell.className = 'text-end';
            actionCell.innerHTML = `
                <div class="d-flex justify-content-end gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewPayment(${p.id})" title="View" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
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

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.changePage = function (page) {
        if (page > 0 && page !== currentPage) {
            currentPage = page;
            loadPayments();
        }
    };

    /* ══════ DETAIL MODAL ══════ */

    window.viewPayment = function (id) {
        currentPaymentId = id;
        const body = document.getElementById('pd_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('pd_public_id').textContent = 'Loading…';
        document.getElementById('pd_company').textContent = '';

        new bootstrap.Modal(document.getElementById('kt_modal_payment_detail')).show();

        fetch(`/admin/payments/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const p = d.data;

                document.getElementById('pd_public_id').textContent = p.public_id;
                document.getElementById('pd_company').textContent =
                    (p.company?.name || '—') + (p.reference ? ' · ' + p.reference : '');

                const attemptsHtml = (p.attempts || []).map(a => `
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-bold">
                                    #${a.attempt_number} ${h(a.operation_label)}
                                    <span class="badge badge-light-${a.status_badge.tone} ms-2">${h(a.status_badge.label)}</span>
                                    ${a.is_retry ? '<span class="badge badge-light-warning ms-1">Retry</span>' : ''}
                                    ${a.is_fallback ? '<span class="badge badge-light-info ms-1">Fallback</span>' : ''}
                                </div>
                                <div class="text-muted fs-7 mt-1">
                                    ${h(a.provider?.name || '—')}
                                    ${a.provider_reference ? ' · ' + h(a.provider_reference) : ''}
                                </div>
                                ${a.provider_message ? `<div class="text-muted fs-7 mt-1">"${h(a.provider_message)}"</div>` : ''}
                                ${a.failure_reason ? `<div class="text-danger fs-7 mt-1">${h(a.failure_reason)}</div>` : ''}
                            </div>
                            <div class="text-end text-muted fs-8">
                                <div>${a.duration_ms ? a.duration_ms + 'ms' : ''}</div>
                                <div>${h(a.completed_at || a.started_at || '')}</div>
                            </div>
                        </div>
                    </div>
                `).join('') || '<p class="text-muted">No attempts yet.</p>';

                const actions = [];
                if (['failed', 'requires_payment_method'].includes(p.status)) {
                    actions.push(`<button class="btn btn-sm btn-light-warning" onclick="retryPayment(${p.id})">Retry</button>`);
                }
                if (['requires_payment_method', 'requires_confirmation', 'requires_action', 'processing'].includes(p.status)) {
                    actions.push(`<button class="btn btn-sm btn-light-danger" onclick="cancelPayment(${p.id})">Cancel</button>`);
                }

                body.innerHTML = `
                    <div class="row g-4 mb-7">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge badge-light-${p.status_badge.tone}">${h(p.status_badge.label)}</span>
                                <span class="badge badge-light-${p.mode_badge.tone}">${h(p.mode_badge.label)}</span>
                                ${p.payment_method_type ? `<span class="badge badge-light-dark">${h(p.payment_method_type.replace(/_/g, ' '))}</span>` : ''}
                                ${p.is_disputed ? '<span class="badge badge-light-danger">Disputed</span>' : ''}
                            </div>
                            <div class="mb-1"><strong>Amount:</strong> ${h(p.amount_display)}</div>
                            ${p.amount_captured ? `<div class="mb-1"><strong>Captured:</strong> ${h(p.amount_captured_display)}</div>` : ''}
                            ${p.provider ? `<div class="mb-1"><strong>Provider:</strong> ${h(p.provider.name)}</div>` : ''}
                            ${p.customer ? `<div class="mb-1"><strong>Customer:</strong> ${h(p.customer.name || p.customer.email)}</div>` : ''}
                            <div class="mb-1"><strong>Created:</strong> ${h(p.created_at)}</div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            ${actions.length ? `<div class="d-flex flex-wrap justify-content-md-end gap-2">${actions.join('')}</div>` : ''}
                        </div>
                    </div>

                    <h4 class="fw-bold mb-3">Attempts (${p.attempts.length})</h4>
                    ${attemptsHtml}

                    ${p.failure_message ? `
                        <h4 class="fw-bold mt-5 mb-3 text-danger">Failure</h4>
                        <div class="alert alert-danger">
                            <strong>${h(p.failure_code || '')}</strong><br>${h(p.failure_message)}
                        </div>
                    ` : ''}

                    ${p.metadata ? `
                        <h4 class="fw-bold mt-5 mb-3">Metadata</h4>
                        <pre class="bg-light p-3 rounded fs-8" style="max-height:200px; overflow:auto;">${h(JSON.stringify(p.metadata, null, 2))}</pre>
                    ` : ''}
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load payment</div>';
            });
    };

    window.retryPayment = function (id) {
        if (!confirm('Retry this payment? A new attempt will be created.')) return;

        fetch(`/admin/payments/${id}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ use_fallback: false }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_payment_detail'))?.hide();
                loadPayments();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

    window.cancelPayment = function (id) {
        if (!confirm('Cancel this payment? It cannot be un-cancelled.')) return;

        fetch(`/admin/payments/${id}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_payment_detail'))?.hide();
                loadPayments();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

})();
</script>