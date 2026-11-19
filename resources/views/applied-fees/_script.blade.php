<script>
(function () {
    'use strict';

    let currentPage = 1;
    let currentCompany = '';
    let currentType = '';
    let currentMode = '';

    const ZERO_DECIMAL = ['UGX','RWF','BIF','XOF','XAF','JPY','KRW','VND','CLP','ISK','XPF'];

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function money(minor, currency = 'UGX') {
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

    document.addEventListener('DOMContentLoaded', function () {
        loadFormOptions();
        loadStats();
        loadFees();

        ['companyFilter', 'typeFilter', 'modeFilter'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', function () {
                currentCompany = document.getElementById('companyFilter').value;
                currentType = document.getElementById('typeFilter').value;
                currentMode = document.getElementById('modeFilter').value;
                currentPage = 1;
                loadStats();
                loadFees();
            });
        });
    });

    function loadFormOptions() {
        fetch('{{ route("admin.applied-fees.form-options") }}')
            .then(r => r.json())
            .then(d => {
                const comp = document.getElementById('companyFilter');
                comp.innerHTML = '<option value="">All companies</option>' +
                    d.companies.map(c => `<option value="${c.value}">${h(c.label)}</option>`).join('');

                const type = document.getElementById('typeFilter');
                type.innerHTML = '<option value="">All fee types</option>' +
                    d.fee_types.map(t => `<option value="${t.value}">${h(t.label)}</option>`).join('');
            })
            .catch(() => {});
    }

    function loadStats() {
        const params = new URLSearchParams();
        if (currentCompany) params.set('company_id', currentCompany);
        if (currentMode) params.set('mode', currentMode);

        fetch('{{ route("admin.applied-fees.stats") }}?' + params.toString())
            .then(r => r.json())
            .then(s => {
                document.getElementById('stat_count').textContent = s.total_count ?? '—';
                document.getElementById('stat_total').textContent = money(s.total_amount || 0);
                document.getElementById('stat_month').textContent = money(s.this_month || 0);
            })
            .catch(() => {});
    }

    function loadFees() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        const params = new URLSearchParams({ page: currentPage, per_page: 25 });
        if (currentCompany) params.set('company_id', currentCompany);
        if (currentType) params.set('fee_type', currentType);
        if (currentMode) params.set('mode', currentMode);

        fetch('{{ route("admin.applied-fees.data") }}?' + params.toString())
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (!data.data.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderFees(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load fees');
            });
    }

    function renderFees(fees) {
        const tbody = document.getElementById('feesTableBody');
        tbody.innerHTML = '';

        fees.forEach(f => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `
                <div class="fw-bold font-monospace">${h(f.feeable_public_id || '—')}</div>
                <div class="text-muted fs-8">${h(f.feeable_type || '')} ${f.mode ? '· ' + h(f.mode) : ''}</div>
            `;

            const compCell = row.insertCell(1);
            compCell.className = 'd-none d-md-table-cell';
            compCell.innerHTML = f.company ? `
                <div class="fw-bold">${h(f.company.name)}</div>
                <div class="text-muted fs-8 font-monospace">${h(f.company.public_id)}</div>
            ` : '—';

            row.insertCell(2).innerHTML = `<span class="badge badge-light-dark">${h(f.fee_type_label)}</span>`;

            const baseCell = row.insertCell(3);
            baseCell.className = 'text-end';
            baseCell.innerHTML = `<span class="font-monospace">${money(f.base_amount, f.currency)}</span>`;

            const feeCell = row.insertCell(4);
            feeCell.className = 'text-end';
            feeCell.innerHTML = `<span class="fw-bold font-monospace">${money(f.total_amount, f.currency)}</span>`;

            const dateCell = row.insertCell(5);
            dateCell.className = 'd-none d-md-table-cell text-muted fs-7';
            dateCell.textContent = f.created_at || '—';

            const actionCell = row.insertCell(6);
            actionCell.className = 'text-end';
            actionCell.innerHTML = `
                <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewFeeDetail(${f.id})" title="View breakdown" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            `;
        });
    }

    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} fees`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); currentPage = page; loadFees(); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.viewFeeDetail = function (id) {
        const body = document.getElementById('fee_detail_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_fee_detail')).show();

        fetch(`/admin/applied-fees/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const f = d.data;
                const snapshot = f.calculation_snapshot || {};

                body.innerHTML = `
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><strong>Fee type:</strong> ${h(f.fee_type_label)}</div>
                        <div class="col-md-6"><strong>Currency:</strong> ${h(f.currency)}</div>
                        <div class="col-md-6"><strong>Base amount:</strong> <span class="font-monospace">${money(f.base_amount, f.currency)}</span></div>
                        <div class="col-md-6"><strong>Total fee:</strong> <span class="font-monospace fw-bold">${money(f.total_amount, f.currency)}</span></div>
                        <div class="col-md-6"><strong>Applied at:</strong> ${h(f.created_at)}</div>
                        <div class="col-md-6"><strong>Company:</strong> ${f.company ? h(f.company.name) : '—'}</div>
                    </div>

                    <h4 class="fw-bold mt-5 mb-3">Calculation Breakdown</h4>
                    <table class="table table-row-dashed fs-7">
                        <tbody>
                            <tr><td>Base amount</td><td class="text-end font-monospace">${money(f.base_amount, f.currency)}</td></tr>
                            <tr><td>Percentage (${h(f.percentage_applied)}%)</td><td class="text-end font-monospace">${money(f.percentage_component, f.currency)}</td></tr>
                            <tr><td>Fixed amount</td><td class="text-end font-monospace">${money(f.fixed_component, f.currency)}</td></tr>
                            <tr><td>Tax on fee</td><td class="text-end font-monospace">${money(f.tax_amount, f.currency)}</td></tr>
                            <tr class="fw-bold border-top"><td>Total</td><td class="text-end font-monospace">${money(f.total_amount, f.currency)}</td></tr>
                        </tbody>
                    </table>

                    ${snapshot && Object.keys(snapshot).length ? `
                        <h4 class="fw-bold mt-5 mb-3">Raw Snapshot</h4>
                        <pre class="bg-light p-3 rounded fs-8" style="max-height:200px; overflow:auto;">${h(JSON.stringify(snapshot, null, 2))}</pre>
                    ` : ''}
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load fee</div>';
            });
    };

})();
</script>