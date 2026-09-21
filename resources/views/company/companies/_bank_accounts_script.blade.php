<script>
/* ═══════════════════════════════════════════════════════
   BANK ACCOUNTS SCRIPT
   Standalone — no dependency on the compliance script
   ═══════════════════════════════════════════════════════ */

(function () {
    'use strict';

    let bankAccountsCompanyId = null;
    let bankAccountsList = [];

    function h(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    /* ─────────────────────────────────────────────
       PUBLIC ENTRY — open modal scoped to a company
       ───────────────────────────────────────────── */

    window.openBankAccounts = function (companyId, companyName) {
        bankAccountsCompanyId = companyId;

        const nameEl = document.getElementById('bank_accounts_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        loadBankAccounts(companyId);

        new bootstrap.Modal(document.getElementById('kt_modal_bank_accounts')).show();
    };

    /* ═════════════════════════════════════════════
       LOAD + RENDER
       ═════════════════════════════════════════════ */

    function loadBankAccounts(companyId) {
        const loading = document.getElementById('comp_banks_loading');
        const empty = document.getElementById('comp_banks_empty');
        const container = document.getElementById('comp_banks_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/bank-accounts`)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('d-none');
                bankAccountsList = data.data || [];

                const countEl = document.getElementById('comp_banks_count');
                if (countEl) countEl.textContent = bankAccountsList.length;

                if (!bankAccountsList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderBankAccounts();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load bank accounts');
            });
    }

    function renderBankAccounts() {
        const body = document.getElementById('comp_banks_body');
        if (!body) return;
        body.innerHTML = '';

        bankAccountsList.forEach(a => {
            const tone = a.status_badge ? a.status_badge.tone : 'secondary';
            const label = a.status_badge ? a.status_badge.label : 'Unknown';
            const defaultBadge = a.is_default
                ? '<span class="badge badge-light-primary ms-1">Default</span>'
                : '';

            const failureHtml = a.failure_reason
                ? `<div class="text-danger fs-8 mt-1" title="${h(a.failure_reason)}">${h(a.failure_reason.substring(0, 30))}…</div>`
                : '';

            let actions = '';

            if (!a.is_default) {
                actions += `
                    <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="setDefaultBank(${a.id})" title="Set as default" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-star fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                `;
            }

            if (a.status !== 'verified') {
                actions += `
                    <button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="changeBankStatus(${a.id}, 'verified')" title="Mark verified" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-check fs-4"></i>
                    </button>
                `;
            }

            if (a.status !== 'disabled') {
                actions += `
                    <button type="button" class="btn btn-sm btn-icon btn-light-warning me-1" onclick="changeBankStatus(${a.id}, 'disabled')" title="Disable" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-minus-circle fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                `;
            }

            actions += `
                <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editBankAccount(${a.id})" title="Edit" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteBankAccount(${a.id})" title="Remove" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i>
                </button>
            `;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="badge badge-light-dark">${h(a.type_label)}</span>
                    ${defaultBadge}
                </td>
                <td>
                    <div class="fw-bold">${h(a.bank_name || a.mobile_network || '—')}</div>
                    <div class="text-muted fs-8 font-monospace">${h(a.masked_account || '—')}</div>
                </td>
                <td>
                    <div>${h(a.account_holder_name)}</div>
                    <div class="text-muted fs-8">${h(a.account_holder_type)}</div>
                </td>
                <td><span class="badge badge-light-primary">${h(a.currency)}</span></td>
                <td>
                    <span class="badge badge-light-${tone}">${h(label)}</span>
                    ${failureHtml}
                </td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    /* ═════════════════════════════════════════════
       ADD / EDIT MODAL
       ═════════════════════════════════════════════ */

    window.openAddBankAccount = function () {
        document.getElementById('bank_modal_title').textContent = 'Add Bank Account';
        document.getElementById('bank_id').value = '';
        document.getElementById('bank_company_id').value = bankAccountsCompanyId;
        document.getElementById('bankAccountForm').reset();
        toggleBankMobileSection();
        new bootstrap.Modal(document.getElementById('kt_modal_bank_account')).show();
    };

    window.editBankAccount = function (id) {
        const a = bankAccountsList.find(x => x.id === id);
        if (!a) return;

        const set = (field, value) => {
            const el = document.getElementById(field);
            if (el) el.value = value || '';
        };

        document.getElementById('bank_modal_title').textContent = 'Edit Bank Account';
        set('bank_id', a.id);
        set('bank_company_id', a.company_id);
        set('bank_type', a.type);
        set('bank_country_code', a.country_code);
        set('bank_currency', a.currency);
        set('bank_account_holder_name', a.account_holder_name);
        set('bank_account_holder_type', a.account_holder_type || 'company');
        set('bank_bank_name', a.bank_name);
        set('bank_bank_code', a.bank_code);
        set('bank_swift_bic', a.swift_bic);
        set('bank_routing_number', a.routing_number);
        set('bank_sort_code', a.sort_code);
        set('bank_mobile_network', a.mobile_network);

        // Blank the sensitive fields — user re-enters only if changing
        document.getElementById('bank_account_number').value = '';
        document.getElementById('bank_iban').value = '';
        document.getElementById('bank_msisdn').value = '';
        document.getElementById('bank_is_default').checked = !!a.is_default;

        toggleBankMobileSection();
        new bootstrap.Modal(document.getElementById('kt_modal_bank_account')).show();
    };

    /* ═════════════════════════════════════════════
       ACTIONS
       ═════════════════════════════════════════════ */

    window.deleteBankAccount = function (id) {
        if (!confirm('Remove this bank account? This cannot be undone.')) return;
        fetch(`/admin/bank-accounts/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadBankAccounts(bankAccountsCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    window.setDefaultBank = function (id) {
        fetch(`/admin/bank-accounts/${id}/set-default`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadBankAccounts(bankAccountsCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    window.changeBankStatus = function (id, status) {
        let reason = null;
        if (status === 'errored' || status === 'disabled') {
            reason = prompt('Reason (optional):');
        }

        fetch(`/admin/bank-accounts/${id}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status, failure_reason: reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadBankAccounts(bankAccountsCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    /* ═════════════════════════════════════════════
       HELPERS
       ═════════════════════════════════════════════ */

    function toggleBankMobileSection() {
        const typeEl = document.getElementById('bank_type');
        const mobileEl = document.getElementById('bank_mobile_section');
        if (!typeEl || !mobileEl) return;

        const type = typeEl.value;
        if (type === 'mobile_money' || type === 'wallet') {
            mobileEl.classList.remove('d-none');
        } else {
            mobileEl.classList.add('d-none');
        }
    }

    document.getElementById('bank_type')?.addEventListener('change', toggleBankMobileSection);

    document.getElementById('bankAccountForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('bankSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('bank_id').value;
        const companyId = document.getElementById('bank_company_id').value;
        const url = id
            ? `/admin/bank-accounts/${id}`
            : `/admin/companies/${companyId}/bank-accounts`;

        const val = (field) => document.getElementById(field)?.value || null;

        const payload = {
            type: val('bank_type'),
            country_code: val('bank_country_code'),
            currency: val('bank_currency'),
            is_default: document.getElementById('bank_is_default').checked ? 1 : 0,
            account_holder_name: val('bank_account_holder_name'),
            account_holder_type: val('bank_account_holder_type'),
            bank_name: val('bank_bank_name'),
            bank_code: val('bank_bank_code'),
            account_number: val('bank_account_number'),
            iban: val('bank_iban'),
            swift_bic: val('bank_swift_bic'),
            routing_number: val('bank_routing_number'),
            sort_code: val('bank_sort_code'),
            mobile_network: val('bank_mobile_network'),
            msisdn: val('bank_msisdn'),
        };

        if (id) payload._method = 'PUT';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_bank_account'))?.hide();
                loadBankAccounts(companyId);
            } else {
                window.showToast('error', data.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

})();
</script>