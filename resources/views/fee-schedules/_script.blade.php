<script>
(function () {
    'use strict';

    let currentPage = 1;
    let currentSearch = '';
    let currentStatus = '';
    let currentDefault = '';
    let schedulesList = [];
    let ruleCounter = 0;

    const FEE_TYPES = [
        { value: 'processing',         label: 'Processing' },
        { value: 'refund',             label: 'Refund' },
        { value: 'payout',             label: 'Payout' },
        { value: 'chargeback',         label: 'Chargeback' },
        { value: 'fx',                 label: 'FX' },
        { value: 'international_card', label: 'Intl Card' },
        { value: 'monthly',            label: 'Monthly' },
    ];

    const PAYMENT_METHODS = ['card', 'mobile_money', 'bank_transfer', 'ussd', 'wallet'];

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    /* ══════ STATS ══════ */

    function loadStats() {
        fetch('{{ route("admin.fee-schedules.stats") }}')
            .then(r => r.json())
            .then(s => {
                document.getElementById('stat_total').textContent = s.total ?? '—';
                document.getElementById('stat_active').textContent = s.active ?? '—';
                document.getElementById('stat_default').textContent = s.default ?? '—';
                document.getElementById('stat_rules').textContent = s.rules ?? '—';
                document.getElementById('stat_assigned').textContent = s.assigned ?? '—';
            })
            .catch(() => {});
    }

    /* ══════ LIST ══════ */

    function loadSchedules() {
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
        if (currentStatus) params.set('status', currentStatus);
        if (currentDefault) params.set('is_default', currentDefault);

        fetch('{{ route("admin.fee-schedules.data") }}?' + params.toString())
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                schedulesList = data.data || [];

                if (!schedulesList.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderTable();
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load schedules');
            });
    }

    function renderTable() {
        const tbody = document.getElementById('schedulesTableBody');
        tbody.innerHTML = '';

        schedulesList.forEach(s => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold">${h(s.name)}</span>
                    ${s.is_default ? '<span class="badge badge-light-primary fs-8">Default</span>' : ''}
                </div>
                ${s.description ? `<div class="text-muted fs-8">${h(s.description)}</div>` : ''}
            `;

            const codeCell = row.insertCell(1);
            codeCell.className = 'd-none d-md-table-cell';
            codeCell.innerHTML = `<span class="font-monospace fs-7">${h(s.code)}</span>`;

            row.insertCell(2).innerHTML = `<span class="badge badge-light-dark">${s.rules_count}</span>`;

            const compCell = row.insertCell(3);
            compCell.className = 'd-none d-lg-table-cell';
            compCell.innerHTML = `<span class="badge badge-light-info">${s.companies_count}</span>`;

            const effCell = row.insertCell(4);
            effCell.className = 'd-none d-md-table-cell text-muted fs-7';
            effCell.innerHTML = s.effective_from
                ? `${h(s.effective_from)}${s.effective_to ? ' → ' + h(s.effective_to) : ' → open'}`
                : '<span class="text-muted">—</span>';

            row.insertCell(5).innerHTML = `<span class="badge badge-light-${s.status_badge.tone}">${h(s.status_badge.label)}</span>`;

            const actionCell = row.insertCell(6);
            actionCell.className = 'text-end';
            actionCell.innerHTML = `
                <div class="d-flex justify-content-end gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editSchedule(${s.id})" title="Edit" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    ${!s.is_default ? `
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="setDefault(${s.id})" title="Set as default" style="width:28px;height:28px;">
                            <i class="ki-duotone ki-star fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    ` : ''}
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="toggleSchedule(${s.id})" title="${s.is_active ? 'Disable' : 'Enable'}" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-${s.is_active ? 'minus-circle' : 'check'} fs-4 ${s.is_active ? 'text-warning' : 'text-success'}"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteSchedule(${s.id}, '${h(s.name).replace(/'/g, "\\'")}')" title="Delete" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </div>
            `;
        });
    }

    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} schedules`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); currentPage = page; loadSchedules(); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    /* ══════ FILTERS ══════ */

    document.addEventListener('DOMContentLoaded', function () {
        loadStats();
        loadSchedules();

        let searchTimer;
        document.getElementById('searchInput')?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadSchedules();
            }, 400);
        });

        document.getElementById('statusFilter')?.addEventListener('change', function () {
            currentStatus = this.value;
            currentPage = 1;
            loadSchedules();
        });

        document.getElementById('defaultFilter')?.addEventListener('change', function () {
            currentDefault = this.value;
            currentPage = 1;
            loadSchedules();
        });
    });

    /* ══════ EDITOR ══════ */

    window.openAddSchedule = function () {
        document.getElementById('fs_modal_title').textContent = 'New Fee Schedule';
        document.getElementById('fs_modal_subtitle').textContent = 'Define rates for a merchant tier';
        document.getElementById('fs_id').value = '';
        document.getElementById('feeScheduleForm').reset();
        document.getElementById('fs_is_active').checked = true;
        document.getElementById('fs_is_default').checked = false;
        document.getElementById('fs_rules_body').innerHTML = '';
        updateRulesEmptyState();
        new bootstrap.Modal(document.getElementById('kt_modal_fee_schedule')).show();
    };

    window.editSchedule = function (id) {
        fetch(`/admin/fee-schedules/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const s = d.data;

                document.getElementById('fs_modal_title').textContent = 'Edit ' + s.name;
                document.getElementById('fs_modal_subtitle').textContent = s.code;
                document.getElementById('fs_id').value = s.id;
                document.getElementById('fs_name').value = s.name || '';
                document.getElementById('fs_code').value = s.code || '';
                document.getElementById('fs_description').value = s.description || '';
                document.getElementById('fs_is_active').checked = !!s.is_active;
                document.getElementById('fs_is_default').checked = !!s.is_default;
                document.getElementById('fs_effective_from').value = s.effective_from ? s.effective_from.substring(0, 10) : '';
                document.getElementById('fs_effective_to').value = s.effective_to ? s.effective_to.substring(0, 10) : '';

                document.getElementById('fs_rules_body').innerHTML = '';
                (s.rules || []).forEach(r => addFeeRule(r));
                updateRulesEmptyState();

                new bootstrap.Modal(document.getElementById('kt_modal_fee_schedule')).show();
            });
    };

    window.addFeeRule = function (rule = {}) {
        const body = document.getElementById('fs_rules_body');
        const idx = ruleCounter++;

        const typeOptions = FEE_TYPES.map(t =>
            `<option value="${t.value}" ${rule.fee_type === t.value ? 'selected' : ''}>${t.label}</option>`
        ).join('');

        const methodOptions = `<option value="">Any</option>` + PAYMENT_METHODS.map(m =>
            `<option value="${m}" ${rule.payment_method === m ? 'selected' : ''}>${m.replace('_', ' ')}</option>`
        ).join('');

        const tr = document.createElement('tr');
        tr.className = 'fee-rule-row';
        tr.dataset.id = rule.id || '';
        tr.dataset.idx = idx;
        tr.innerHTML = `
            <td><select class="form-select form-select-sm rule-fee-type">${typeOptions}</select></td>
            <td><select class="form-select form-select-sm rule-method">${methodOptions}</select></td>
            <td><input type="text" class="form-control form-control-sm text-uppercase rule-currency" value="${h(rule.currency || '')}" maxlength="3" placeholder="Any" /></td>
            <td><input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm rule-percentage" value="${rule.percentage ?? 0}" /></td>
            <td><input type="number" min="0" class="form-control form-control-sm rule-fixed" value="${rule.fixed_amount ?? 0}" /></td>
            <td><input type="number" min="0" class="form-control form-control-sm rule-min" value="${rule.minimum_fee ?? ''}" placeholder="—" /></td>
            <td><input type="number" min="0" class="form-control form-control-sm rule-max" value="${rule.maximum_fee ?? ''}" placeholder="—" /></td>
            <td><input type="number" step="0.001" min="0" max="100" class="form-control form-control-sm rule-tax" value="${rule.tax_percentage ?? 0}" /></td>
            <td><input type="number" min="1" max="1000" class="form-control form-control-sm rule-priority" value="${rule.priority ?? 100}" /></td>
            <td class="text-center">
                <input class="form-check-input rule-active" type="checkbox" ${rule.is_active !== false ? 'checked' : ''} />
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-icon btn-light-danger" onclick="removeFeeRule(this)" title="Remove" style="width:24px;height:24px;">
                    <i class="ki-duotone ki-trash fs-5 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            </td>
        `;
        body.appendChild(tr);
        updateRulesEmptyState();
    };

    window.removeFeeRule = function (btn) {
        btn.closest('tr').remove();
        updateRulesEmptyState();
    };

    function updateRulesEmptyState() {
        const has = document.querySelectorAll('#fs_rules_body .fee-rule-row').length > 0;
        document.getElementById('fs_rules_empty').classList.toggle('d-none', has);
    }

    /* ══════ SUBMIT ══════ */

    document.getElementById('feeScheduleForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('fsSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('fs_id').value;

        const rules = [];
        document.querySelectorAll('#fs_rules_body .fee-rule-row').forEach(row => {
            rules.push({
                id: row.dataset.id ? parseInt(row.dataset.id) : null,
                fee_type: row.querySelector('.rule-fee-type').value,
                payment_method: row.querySelector('.rule-method').value || null,
                currency: row.querySelector('.rule-currency').value || null,
                percentage: parseFloat(row.querySelector('.rule-percentage').value) || 0,
                fixed_amount: parseInt(row.querySelector('.rule-fixed').value) || 0,
                minimum_fee: row.querySelector('.rule-min').value ? parseInt(row.querySelector('.rule-min').value) : null,
                maximum_fee: row.querySelector('.rule-max').value ? parseInt(row.querySelector('.rule-max').value) : null,
                tax_percentage: parseFloat(row.querySelector('.rule-tax').value) || 0,
                priority: parseInt(row.querySelector('.rule-priority').value) || 100,
                is_active: row.querySelector('.rule-active').checked ? 1 : 0,
            });
        });

        const payload = {
            name: document.getElementById('fs_name').value,
            code: document.getElementById('fs_code').value,
            description: document.getElementById('fs_description').value || null,
            is_active: document.getElementById('fs_is_active').checked ? 1 : 0,
            is_default: document.getElementById('fs_is_default').checked ? 1 : 0,
            effective_from: document.getElementById('fs_effective_from').value || null,
            effective_to: document.getElementById('fs_effective_to').value || null,
            rules,
        };

        const url = id
            ? `/admin/fee-schedules/${id}`
            : '{{ route("admin.fee-schedules.store") }}';
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
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_fee_schedule'))?.hide();
                loadStats();
                loadSchedules();
            } else {
                window.showToast('error', d.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ══════ ROW ACTIONS ══════ */

    window.toggleSchedule = function (id) {
        fetch(`/admin/fee-schedules/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadSchedules();
            } else window.showToast('error', d.message);
        });
    };

    window.setDefault = function (id) {
        if (!confirm('Set this schedule as the platform default? It will be used for any company without an explicit schedule.')) return;
        fetch(`/admin/fee-schedules/${id}/set-default`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadSchedules();
            } else window.showToast('error', d.message);
        });
    };

    window.deleteSchedule = function (id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        fetch(`/admin/fee-schedules/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadStats();
                loadSchedules();
            } else window.showToast('error', d.message);
        });
    };

})();
</script>