<script>
(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════
       STATE
       ═══════════════════════════════════════════════════════ */

    let invoicesCompanyId = null;
    let invoicesList = [];
    let currentInvoicePage = 1;
    let customersCache = [];
    let taxRatesCache = [];
    let productsCache = [];
    let searchDebounce = null;

    const ZERO_DECIMAL = ['UGX','RWF','BIF','XOF','XAF','JPY','KRW','VND','CLP','ISK','XPF'];

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function qs(s) {
        return String(s || '').replace(/'/g, "\\'");
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
        return ZERO_DECIMAL.includes(currency)
            ? Math.round(num)
            : Math.round(num * 100);
    }

    function fromMinor(minor, currency) {
        return ZERO_DECIMAL.includes(currency) ? minor : minor / 100;
    }

    /* ═══════════════════════════════════════════════════════
       OPEN MODAL
       ═══════════════════════════════════════════════════════ */

    window.openInvoices = function (companyId, companyName) {
        if (!companyId) {
            window.showToast('error', 'Missing company ID');
            return;
        }

        invoicesCompanyId = companyId;
        const nameEl = document.getElementById('invoices_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        currentInvoicePage = 1;
        loadCustomers();
        loadTaxRates();
        loadInvoices();

        new bootstrap.Modal(document.getElementById('kt_modal_invoices')).show();
    };

    /* ═══════════════════════════════════════════════════════
       LOAD CACHES
       ═══════════════════════════════════════════════════════ */

    function loadCustomers() {
        fetch(`/admin/companies/${invoicesCompanyId}/customers?per_page=200`)
            .then(r => r.json())
            .then(data => {
                customersCache = data.data || [];
                const sel = document.getElementById('inv_customer_id');
                if (sel) {
                    sel.innerHTML = '<option value="">— Manual entry —</option>' +
                        customersCache.map(c => `<option value="${c.id}">${h(c.display_name || c.name || c.email || c.public_id)}</option>`).join('');
                }
            })
            .catch(() => {});
    }

    function loadTaxRates() {
        fetch(`/admin/companies/${invoicesCompanyId}/tax-rates`)
            .then(r => r.json())
            .then(data => {
                taxRatesCache = (data.data || []).filter(t => t.is_active);
            })
            .catch(() => {});
    }

    /* ═══════════════════════════════════════════════════════
       LIST
       ═══════════════════════════════════════════════════════ */

    function loadInvoices() {
        const loading = document.getElementById('inv_loading');
        const empty = document.getElementById('inv_empty');
        const container = document.getElementById('inv_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams({ page: currentInvoicePage, per_page: 20 });
        const search = document.getElementById('invSearch')?.value;
        const mode = document.getElementById('invModeFilter')?.value;
        const status = document.getElementById('invStatusFilter')?.value;
        if (search) params.set('search', search);
        if (mode) params.set('mode', mode);
        if (status) params.set('status', status);

        fetch(`/admin/companies/${invoicesCompanyId}/invoices?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                invoicesList = data.data || [];

                if (!invoicesList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderInvoices();
                renderInvoicesPagination(data);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load invoices');
            });
    }

    function renderInvoices() {
        const body = document.getElementById('inv_body');
        if (!body) return;
        body.innerHTML = '';

        invoicesList.forEach(inv => {
            const customerLabel = inv.customer
                ? (inv.customer.name || inv.customer.email)
                : (inv.customer_name || inv.customer_email || '—');

            const numberLabel = inv.number
                ? `<span class="font-monospace">${h(inv.number)}</span>`
                : `<span class="badge badge-light-secondary">Draft</span>`;

            let actions = '';
            actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="viewInvoice(${inv.id})" title="View" style="width:28px;height:28px;"><i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>`;
            if (inv.is_editable) {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editInvoice(${inv.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;
            }
            if (inv.status === 'draft') {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="finalizeInvoice(${inv.id})" title="Finalize" style="width:28px;height:28px;"><i class="ki-duotone ki-check fs-4"></i></button>`;
            } else if (['open','partially_paid','past_due'].includes(inv.status)) {
                actions += `<button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="markInvoicePaid(${inv.id})" title="Mark paid" style="width:28px;height:28px;"><i class="ki-duotone ki-dollar fs-4"><span class="path1"></span><span class="path2"></span></i></button>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${numberLabel}</div>
                    <div class="text-muted fs-8">
                        <span class="badge badge-light-${inv.mode_badge.tone} fs-8">${h(inv.mode_badge.label)}</span>
                        ${inv.is_overdue ? '<span class="badge badge-light-danger fs-8 ms-1">Overdue</span>' : ''}
                    </div>
                </td>
                <td class="d-none d-md-table-cell">
                    <div>${h(customerLabel)}</div>
                    ${inv.customer_email ? `<div class="text-muted fs-8">${h(inv.customer_email)}</div>` : ''}
                </td>
                <td>
                    <div class="fw-bold">${money(inv.total, inv.currency)}</div>
                    ${inv.amount_due > 0 && inv.amount_due < inv.total
                        ? `<div class="text-muted fs-8">Due: ${money(inv.amount_due, inv.currency)}</div>`
                        : ''}
                </td>
                <td class="d-none d-lg-table-cell text-muted">${h(inv.due_date || '—')}</td>
                <td><span class="badge badge-light-${inv.status_badge.tone}">${h(inv.status_badge.label)}</span></td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    function renderInvoicesPagination(data) {
        const info = document.getElementById('inv_info');
        const el = document.getElementById('inv_pager');
        if (!el) return;

        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} invoices`;

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled) a.onclick = (e) => { e.preventDefault(); currentInvoicePage = page; loadInvoices(); };
            li.appendChild(a);
            el.appendChild(li);
        };

        add(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        add(data.current_page, data.current_page, true);
        add(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    document.getElementById('invSearch')?.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            currentInvoicePage = 1;
            loadInvoices();
        }, 400);
    });

    document.getElementById('invModeFilter')?.addEventListener('change', () => {
        currentInvoicePage = 1;
        loadInvoices();
    });

    document.getElementById('invStatusFilter')?.addEventListener('change', () => {
        currentInvoicePage = 1;
        loadInvoices();
    });

    /* ═══════════════════════════════════════════════════════
       EDITOR — OPEN
       ═══════════════════════════════════════════════════════ */

    window.openAddInvoice = function () {
        document.getElementById('inv_editor_title').textContent = 'New Invoice';
        document.getElementById('inv_editor_subtitle').textContent = 'Draft — no number yet';
        document.getElementById('inv_id').value = '';
        document.getElementById('inv_company_id').value = invoicesCompanyId;
        document.getElementById('invoiceForm').reset();
        document.getElementById('inv_lines_body').innerHTML = '';
        document.getElementById('inv_discount_id').value = '';
        document.getElementById('inv_discount_feedback').textContent = '';

        // Defaults
        document.getElementById('inv_currency').value = 'USD';
        document.getElementById('inv_issue_date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('inv_auto_reminders_enabled').checked = true;

        loadCustomers();
        loadTaxRates();

        addBlankInvoiceLine();

        new bootstrap.Modal(document.getElementById('kt_modal_invoice_editor')).show();
    };

    window.editInvoice = function (id) {
        fetch(`/admin/invoices/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const inv = d.data;

                document.getElementById('inv_editor_title').textContent = 'Edit ' + (inv.number || 'Draft');
                document.getElementById('inv_editor_subtitle').textContent =
                    inv.number ? 'Finalized ' + (inv.finalized_at || '') : 'Draft — no number yet';

                document.getElementById('inv_id').value = inv.id;
                document.getElementById('inv_company_id').value = inv.company_id;
                document.getElementById('inv_mode').value = inv.mode;
                document.getElementById('inv_currency').value = inv.currency;
                document.getElementById('inv_issue_date').value = inv.issue_date || '';
                document.getElementById('inv_due_date').value = inv.due_date || '';
                document.getElementById('inv_customer_id').value = inv.customer?.id || '';
                document.getElementById('inv_customer_name').value = inv.customer_name || '';
                document.getElementById('inv_customer_email').value = inv.customer_email || '';
                document.getElementById('inv_customer_phone').value = inv.customer_phone || '';
                document.getElementById('inv_notes').value = inv.notes || '';
                document.getElementById('inv_terms').value = inv.terms || '';
                document.getElementById('inv_internal_notes').value = inv.internal_notes || '';
                document.getElementById('inv_allow_partial_payment').checked = !!inv.allow_partial_payment;
                document.getElementById('inv_auto_reminders_enabled').checked = !!inv.auto_reminders_enabled;

                if (inv.discount) {
                    document.getElementById('inv_discount_id').value = inv.discount.id;
                    document.getElementById('inv_discount_code').value = inv.discount.code || '';
                    document.getElementById('inv_discount_feedback').innerHTML =
                        `<span class="text-success">${h(inv.discount.label)} applied</span>`;
                } else {
                    document.getElementById('inv_discount_id').value = '';
                    document.getElementById('inv_discount_code').value = '';
                    document.getElementById('inv_discount_feedback').textContent = '';
                }

                // Render line items
                const body = document.getElementById('inv_lines_body');
                body.innerHTML = '';
                inv.items.forEach(item => addInvoiceLine(item));

                if (!inv.items.length) addBlankInvoiceLine();

                recalcInvoiceTotals();

                new bootstrap.Modal(document.getElementById('kt_modal_invoice_editor')).show();
            });
    };

    /* ═══════════════════════════════════════════════════════
       EDITOR — LINE ITEMS
       ═══════════════════════════════════════════════════════ */

    window.addBlankInvoiceLine = function () {
        addInvoiceLine({
            name: '',
            description: '',
            quantity: 1,
            unit_amount: 0,
            tax_rate_id: null,
            tax_percentage: 0,
        });
    };

    function addInvoiceLine(item) {
        const body = document.getElementById('inv_lines_body');
        if (!body) return;

        const currency = document.getElementById('inv_currency')?.value || 'USD';
        const unitAmount = item.unit_amount != null
            ? fromMinor(item.unit_amount, currency)
            : '';

        const taxOptions = taxRatesCache.map(t => {
            const sel = (item.tax_rate_id == t.id) ? 'selected' : '';
            return `<option value="${t.id}" data-percentage="${t.percentage}" ${sel}>${h(t.label)}</option>`;
        }).join('');

        const row = document.createElement('tr');
        row.className = 'inv-line';
        row.innerHTML = `
            <td>
                <input type="text" class="form-control form-control-sm inv-line-name" value="${h(item.name || '')}" placeholder="Item description" />
                <input type="text" class="form-control form-control-sm mt-1 inv-line-desc" value="${h(item.description || '')}" placeholder="Optional detail" />
                <input type="hidden" class="inv-line-product-id" value="${item.product_id || ''}" />
                <input type="hidden" class="inv-line-price-id" value="${item.price_id || ''}" />
            </td>
            <td>
                <input type="number" step="0.0001" min="0" class="form-control form-control-sm inv-line-qty" value="${item.quantity || 1}" />
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm inv-line-unit" value="${unitAmount}" />
            </td>
            <td>
                <select class="form-select form-select-sm inv-line-tax">
                    <option value="">No tax</option>
                    ${taxOptions}
                </select>
            </td>
            <td class="text-end">
                <span class="fw-bold inv-line-total">—</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-light-danger" onclick="removeInvoiceLine(this)" title="Remove" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-trash fs-5 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            </td>
        `;
        body.appendChild(row);

        // If the item had a tax_rate_id not in the cache (rare), add a free-text fallback
        if (item.tax_rate_id && !taxRatesCache.find(t => t.id == item.tax_rate_id) && item.tax_percentage) {
            const sel = row.querySelector('.inv-line-tax');
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = `Custom: ${item.tax_percentage}%`;
            opt.dataset.percentage = item.tax_percentage;
            opt.selected = true;
            sel.appendChild(opt);
        }

        // Live recalc
        row.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', recalcInvoiceTotals);
            el.addEventListener('change', recalcInvoiceTotals);
        });

        recalcInvoiceTotals();
    }

    window.removeInvoiceLine = function (btn) {
        btn.closest('tr')?.remove();
        recalcInvoiceTotals();
    };

    function readInvoiceLines() {
        const currency = document.getElementById('inv_currency')?.value || 'USD';
        const rows = document.querySelectorAll('#inv_lines_body .inv-line');
        const items = [];

        rows.forEach(row => {
            const name = row.querySelector('.inv-line-name')?.value?.trim();
            if (!name) return;

            const qty = parseFloat(row.querySelector('.inv-line-qty')?.value) || 0;
            const unitMajor = parseFloat(row.querySelector('.inv-line-unit')?.value) || 0;
            const unitMinor = toMinor(unitMajor, currency);

            const taxSel = row.querySelector('.inv-line-tax');
            const taxRateId = taxSel?.value || null;
            const taxPct = taxSel?.selectedOptions?.[0]?.dataset?.percentage
                ? parseFloat(taxSel.selectedOptions[0].dataset.percentage)
                : 0;

            items.push({
                product_id: row.querySelector('.inv-line-product-id')?.value || null,
                price_id: row.querySelector('.inv-line-price-id')?.value || null,
                name: name,
                description: row.querySelector('.inv-line-desc')?.value || null,
                quantity: qty,
                unit_amount: unitMinor,
                currency: currency,
                tax_rate_id: taxRateId ? parseInt(taxRateId) : null,
                tax_percentage: taxPct,
            });
        });

        return items;
    }

    /* ═══════════════════════════════════════════════════════
       TOTALS
       ═══════════════════════════════════════════════════════ */

    function recalcInvoiceTotals() {
        const currency = document.getElementById('inv_currency')?.value || 'USD';
        const rows = document.querySelectorAll('#inv_lines_body .inv-line');

        let subtotal = 0;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-line-qty')?.value) || 0;
            const unitMajor = parseFloat(row.querySelector('.inv-line-unit')?.value) || 0;
            const unitMinor = toMinor(unitMajor, currency);
            const lineMinor = Math.round(qty * unitMinor);

            subtotal += lineMinor;

            const totalEl = row.querySelector('.inv-line-total');
            if (totalEl) totalEl.textContent = money(lineMinor, currency);
        });

        // Discount
        const discountId = document.getElementById('inv_discount_id')?.value;
        let discountMinor = 0;
        if (discountId) {
            discountMinor = computeDiscountFromCache(discountId, subtotal);
        }

        // Tax — computed on discounted amounts (mirrors server-side)
        let taxTotal = 0;
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-line-qty')?.value) || 0;
            const unitMajor = parseFloat(row.querySelector('.inv-line-unit')?.value) || 0;
            const unitMinor = toMinor(unitMajor, currency);
            const lineMinor = Math.round(qty * unitMinor);

            const taxSel = row.querySelector('.inv-line-tax');
            const taxPct = taxSel?.selectedOptions?.[0]?.dataset?.percentage
                ? parseFloat(taxSel.selectedOptions[0].dataset.percentage)
                : 0;

            const lineDiscount = subtotal > 0 ? Math.round(lineMinor / subtotal * discountMinor) : 0;
            const lineAfterDiscount = lineMinor - lineDiscount;
            taxTotal += Math.round(lineAfterDiscount * taxPct / 100);
        });

        const total = subtotal - discountMinor + taxTotal;

        document.getElementById('inv_subtotal_display').textContent = money(subtotal, currency);
        document.getElementById('inv_discount_display').textContent = discountMinor ? '- ' + money(discountMinor, currency) : '—';
        document.getElementById('inv_tax_display').textContent = money(taxTotal, currency);
        document.getElementById('inv_total_display').textContent = money(total, currency);
    }

    function computeDiscountFromCache(discountId, subtotalMinor) {
        // We only cache what we get from the validate endpoint; fall back to 0 if missing
        const cached = window.__inv_discount_cache?.[discountId];
        if (!cached) return 0;

        if (cached.type === 'percentage') {
            return Math.round(subtotalMinor * (parseFloat(cached.percent_off) / 100));
        }
        return Math.min(cached.amount_off, subtotalMinor);
    }

    /* ═══════════════════════════════════════════════════════
       DISCOUNT VALIDATION
       ═══════════════════════════════════════════════════════ */

    window.applyInvoiceDiscount = function () {
        const code = document.getElementById('inv_discount_code')?.value?.trim();
        const feedback = document.getElementById('inv_discount_feedback');

        if (!code) {
            feedback.innerHTML = '<span class="text-warning">Enter a code first</span>';
            return;
        }

        const currency = document.getElementById('inv_currency').value;
        const subtotal = sumLineSubtotals(currency);

        fetch(`/admin/companies/${invoicesCompanyId}/discounts/validate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code,
                subtotal,
                currency,
                mode: document.getElementById('inv_mode').value,
            }),
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                document.getElementById('inv_discount_id').value = '';
                feedback.innerHTML = `<span class="text-danger">${h(d.message)}</span>`;
                recalcInvoiceTotals();
                return;
            }

            const info = d.data;
            document.getElementById('inv_discount_id').value = info.id;

            // Cache the discount details for local computation
            window.__inv_discount_cache = window.__inv_discount_cache || {};
            window.__inv_discount_cache[info.id] = {
                type: code.startsWith('FLAT') ? 'fixed_amount' : 'percentage',
                // We don't know the type from the API response here — use the amount to figure it out
                // Simplest: fetch a wider set of discount info, but for now we infer
                amount_off: info.amount_discounted,
                percent_off: subtotal > 0 ? (info.amount_discounted / subtotal * 100) : 0,
            };

            feedback.innerHTML = `<span class="text-success">${h(info.label)} applied — ${money(info.amount_discounted, currency)} off</span>`;
            recalcInvoiceTotals();
        })
        .catch(() => {
            feedback.innerHTML = '<span class="text-danger">Failed to validate code</span>';
        });
    };

    window.clearInvoiceDiscount = function () {
        document.getElementById('inv_discount_id').value = '';
        document.getElementById('inv_discount_code').value = '';
        document.getElementById('inv_discount_feedback').textContent = '';
        recalcInvoiceTotals();
    };

    function sumLineSubtotals(currency) {
        let subtotal = 0;
        document.querySelectorAll('#inv_lines_body .inv-line').forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-line-qty')?.value) || 0;
            const unitMajor = parseFloat(row.querySelector('.inv-line-unit')?.value) || 0;
            subtotal += Math.round(qty * toMinor(unitMajor, currency));
        });
        return subtotal;
    }

    /* ═══════════════════════════════════════════════════════
       CUSTOMER AUTO-FILL
       ═══════════════════════════════════════════════════════ */

    document.getElementById('inv_customer_id')?.addEventListener('change', function () {
        const id = this.value;
        if (!id) return;

        const c = customersCache.find(x => x.id == id);
        if (!c) return;

        document.getElementById('inv_customer_name').value = c.name || '';
        document.getElementById('inv_customer_email').value = c.email || '';
        document.getElementById('inv_customer_phone').value = c.phone || '';
    });

    /* ═══════════════════════════════════════════════════════
       SUBMIT
       ═══════════════════════════════════════════════════════ */

    document.getElementById('invoiceForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('invSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('inv_id').value;
        const companyId = document.getElementById('inv_company_id').value;

        const items = readInvoiceLines();
        if (!items.length) {
            window.showToast('warning', 'Add at least one line item.');
            window.hideButtonSpinner(btn);
            return;
        }

        const payload = {
            mode: document.getElementById('inv_mode').value,
            currency: document.getElementById('inv_currency').value,
            customer_id: document.getElementById('inv_customer_id').value || null,
            customer_name: document.getElementById('inv_customer_name').value || null,
            customer_email: document.getElementById('inv_customer_email').value || null,
            customer_phone: document.getElementById('inv_customer_phone').value || null,
            discount_id: document.getElementById('inv_discount_id').value || null,
            issue_date: document.getElementById('inv_issue_date').value || null,
            due_date: document.getElementById('inv_due_date').value || null,
            notes: document.getElementById('inv_notes').value || null,
            terms: document.getElementById('inv_terms').value || null,
            internal_notes: document.getElementById('inv_internal_notes').value || null,
            allow_partial_payment: document.getElementById('inv_allow_partial_payment').checked ? 1 : 0,
            auto_reminders_enabled: document.getElementById('inv_auto_reminders_enabled').checked ? 1 : 0,
            items,
        };

        const url = id ? `/admin/invoices/${id}` : `/admin/companies/${companyId}/invoices`;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_editor'))?.hide();
                loadInvoices();
            } else {
                window.showToast('error', d.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ═══════════════════════════════════════════════════════
       LIFECYCLE ACTIONS
       ═══════════════════════════════════════════════════════ */

    window.finalizeInvoice = function (id) {
        if (!confirm('Finalize this invoice? It will be assigned a number and cannot be edited afterward.')) return;

        fetch(`/admin/invoices/${id}/finalize`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to finalize'));
    };

    window.sendInvoice = function (id) {
        if (!confirm('Send this invoice to the customer?')) return;

        fetch(`/admin/invoices/${id}/send`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to send'));
    };

    window.markInvoicePaid = function (id) {
        const inv = invoicesList.find(x => x.id === id);
        if (!inv) return;

        const remaining = inv.amount_due || inv.total;
        const input = prompt(
            `Record payment. Amount due: ${money(remaining, inv.currency)}\n\nEnter amount received (leave blank for full payment):`,
            ''
        );

        if (input === null) return;

        const amount = input.trim() === '' ? remaining : toMinor(input, inv.currency);

        fetch(`/admin/invoices/${id}/mark-paid`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ amount }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to record payment'));
    };

    window.voidInvoice = function (id) {
        const reason = prompt('Void this invoice? Enter a reason (optional):');
        if (reason === null) return;

        fetch(`/admin/invoices/${id}/void`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ reason }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to void'));
    };

    window.duplicateInvoice = function (id) {
        if (!confirm('Duplicate this invoice as a new draft?')) return;

        fetch(`/admin/invoices/${id}/duplicate`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to duplicate'));
    };

    window.deleteInvoice = function (id, label) {
        if (!confirm(`Delete "${label}"? Only draft or void invoices can be deleted.`)) return;

        fetch(`/admin/invoices/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadInvoices();
            } else window.showToast('error', d.message);
        })
        .catch(() => window.showToast('error', 'Failed to delete'));
    };

    /* ═══════════════════════════════════════════════════════
       DETAIL VIEW
       ═══════════════════════════════════════════════════════ */

    window.viewInvoice = function (id) {
        const body = document.getElementById('inv_detail_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        new bootstrap.Modal(document.getElementById('kt_modal_invoice_detail')).show();

        fetch(`/admin/invoices/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const inv = d.data;

                document.getElementById('inv_detail_number').textContent = inv.number || 'Draft Invoice';
                document.getElementById('inv_detail_customer').textContent =
                    (inv.customer_name || inv.customer?.name || '—') +
                    (inv.customer_email || inv.customer?.email ? ` · ${inv.customer_email || inv.customer?.email}` : '');

                const itemsHtml = inv.items.map(item => `
                    <tr>
                        <td>
                            <div class="fw-semibold">${h(item.name)}</div>
                            ${item.description ? `<div class="text-muted fs-8">${h(item.description)}</div>` : ''}
                        </td>
                        <td>${item.quantity}</td>
                        <td class="text-end">${h(item.unit_amount_display)}</td>
                        <td class="text-end">${item.tax_percentage ? h(item.tax_percentage) + '%' : '—'}</td>
                        <td class="text-end fw-bold">${h(item.total_display)}</td>
                    </tr>
                `).join('');

                const actions = [];
                if (inv.status === 'draft') {
                    actions.push(`<button class="btn btn-sm btn-success" onclick="finalizeInvoice(${inv.id}); bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide();">Finalize</button>`);
                }
                if (inv.is_editable) {
                    actions.push(`<button class="btn btn-sm btn-light-primary" onclick="bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide(); editInvoice(${inv.id});">Edit</button>`);
                }
                if (inv.number) {
                    actions.push(`<button class="btn btn-sm btn-light-info" onclick="sendInvoice(${inv.id}); bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide();">Send</button>`);
                }
                if (['open','partially_paid','past_due'].includes(inv.status)) {
                    actions.push(`<button class="btn btn-sm btn-light-success" onclick="markInvoicePaid(${inv.id}); bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide();">Mark Paid</button>`);
                }
                if (!['void','paid'].includes(inv.status)) {
                    actions.push(`<button class="btn btn-sm btn-light-warning" onclick="bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide(); voidInvoice(${inv.id});">Void</button>`);
                }
                actions.push(`<button class="btn btn-sm btn-light" onclick="bootstrap.Modal.getInstance(document.getElementById('kt_modal_invoice_detail')).hide(); duplicateInvoice(${inv.id});">Duplicate</button>`);

                body.innerHTML = `
                    <div class="row g-4 mb-7">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge badge-light-${inv.status_badge.tone}">${h(inv.status_badge.label)}</span>
                                <span class="badge badge-light-${inv.mode_badge.tone}">${h(inv.mode_badge.label)}</span>
                                ${inv.is_overdue ? '<span class="badge badge-light-danger">Overdue</span>' : ''}
                            </div>
                            <div class="text-muted fs-7 mb-1"><strong>Issued:</strong> ${h(inv.issue_date || '—')}</div>
                            <div class="text-muted fs-7 mb-1"><strong>Due:</strong> ${h(inv.due_date || '—')}</div>
                            <div class="text-muted fs-7"><strong>Currency:</strong> ${h(inv.currency)}</div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-muted fs-7">Total</div>
                            <div class="fs-2 fw-bold">${money(inv.total, inv.currency)}</div>
                            ${inv.amount_paid > 0 ? `
                                <div class="text-success fs-7 mt-2">Paid: ${money(inv.amount_paid, inv.currency)}</div>
                                ${inv.amount_due > 0 ? `<div class="text-danger fs-7">Due: ${money(inv.amount_due, inv.currency)}</div>` : ''}
                            ` : ''}
                        </div>
                    </div>

                    <div class="table-responsive mb-5">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th class="text-end">Unit</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mb-7">
                        <div class="col-md-5">
                            <div class="border rounded p-4 bg-light">
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span class="fw-bold">${money(inv.subtotal, inv.currency)}</span></div>
                                ${inv.discount_total ? `<div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount${inv.discount ? ' ('+h(inv.discount.code)+')' : ''}</span><span class="fw-bold text-danger">- ${money(inv.discount_total, inv.currency)}</span></div>` : ''}
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax</span><span class="fw-bold">${money(inv.tax_total, inv.currency)}</span></div>
                                <div class="separator my-2"></div>
                                <div class="d-flex justify-content-between"><span class="fw-bold">Total</span><span class="fw-bold fs-3">${money(inv.total, inv.currency)}</span></div>
                            </div>
                        </div>
                    </div>

                    ${inv.notes ? `<div class="mb-4"><h4 class="fw-bold mb-2">Notes</h4><div class="text-muted">${h(inv.notes)}</div></div>` : ''}
                    ${inv.terms ? `<div class="mb-4"><h4 class="fw-bold mb-2">Terms</h4><div class="text-muted">${h(inv.terms)}</div></div>` : ''}

                    <div class="d-flex flex-wrap gap-2 pt-5 border-top">
                        ${actions.join('')}
                    </div>
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load invoice</div>';
            });
    };

    /* ═══════════════════════════════════════════════════════
       CATALOG PICKER
       ═══════════════════════════════════════════════════════ */

    window.openCatalogPicker = function () {
        const body = document.getElementById('catalog_picker_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_catalog_picker')).show();

        fetch(`/admin/companies/${invoicesCompanyId}/products?per_page=100`)
            .then(r => r.json())
            .then(d => {
                productsCache = d.data || [];

                if (!productsCache.length) {
                    body.innerHTML = `
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <p class="text-muted">No products yet. <a href="#" onclick="bootstrap.Modal.getInstance(document.getElementById('kt_modal_catalog_picker')).hide(); return false;">Add some in the Catalog tab.</a></p>
                        </div>
                    `;
                    return;
                }

                const currency = document.getElementById('inv_currency')?.value || 'USD';

                const rows = productsCache.map(p => `
                    <tr>
                        <td>
                            <div class="fw-semibold">${h(p.name)}</div>
                            ${p.sku ? `<div class="text-muted fs-8 font-monospace">${h(p.sku)}</div>` : ''}
                        </td>
                        <td>
                            <select class="form-select form-select-sm picker-price-select" data-product-id="${p.id}">
                                <option value="">Select price…</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="pickProductWithPrice(${p.id})">Add</button>
                        </td>
                    </tr>
                `).join('');

                body.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;

                // Populate price dropdowns
                productsCache.forEach(p => {
                    fetch(`/admin/products/${p.id}/prices`)
                        .then(r => r.json())
                        .then(pd => {
                            const sel = document.querySelector(`.picker-price-select[data-product-id="${p.id}"]`);
                            if (!sel) return;
                            const prices = (pd.data || []).filter(pr => pr.is_active);
                            sel.innerHTML = '<option value="">Select price…</option>' +
                                prices.map(pr => `<option value="${pr.id}" data-amount="${pr.unit_amount}" data-currency="${pr.currency}" data-nickname="${h(pr.nickname || pr.interval_label)}">${h(pr.nickname || pr.interval_label)} — ${money(pr.unit_amount, pr.currency)}</option>`).join('');
                        });
                });
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load products</div>';
            });
    };

    window.pickProductWithPrice = function (productId) {
        const sel = document.querySelector(`.picker-price-select[data-product-id="${productId}"]`);
        if (!sel || !sel.value) {
            window.showToast('warning', 'Select a price first.');
            return;
        }

        const product = productsCache.find(p => p.id === productId);
        const opt = sel.selectedOptions[0];
        const unitMinor = parseInt(opt.dataset.amount);
        const currency = opt.dataset.currency || 'USD';

        // Set the invoice currency to match if it's currently blank or defaulting
        const currentCurrency = document.getElementById('inv_currency').value;
        if (!currentCurrency) document.getElementById('inv_currency').value = currency;

        addInvoiceLine({
            product_id: productId,
            price_id: parseInt(sel.value),
            name: product.name,
            description: product.description || '',
            quantity: 1,
            unit_amount: unitMinor,
            currency,
            tax_rate_id: null,
            tax_percentage: 0,
        });

        bootstrap.Modal.getInstance(document.getElementById('kt_modal_catalog_picker')).hide();
    };

    /* ═══════════════════════════════════════════════════════
       CURRENCY CHANGE → RE-RENDER LINES
       ═══════════════════════════════════════════════════════ */

    document.getElementById('inv_currency')?.addEventListener('change', function () {
        // Re-read lines so amounts get re-interpreted in the new currency
        const items = readInvoiceLines();
        const body = document.getElementById('inv_lines_body');
        body.innerHTML = '';
        items.forEach(i => addInvoiceLine(i));
        if (!items.length) addBlankInvoiceLine();
        recalcInvoiceTotals();
    });

})();
</script>