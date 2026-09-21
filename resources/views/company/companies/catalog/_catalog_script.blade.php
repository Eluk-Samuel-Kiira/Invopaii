<script>
(function () {
    'use strict';

    let catalogCompanyId = null;
    let productsList = [];
    let taxRatesList = [];
    let discountsList = [];
    let currentProductPage = 1;

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function qs(s) { return String(s || '').replace(/'/g, "\\'"); }

    /* ══════════════ OPEN ══════════════ */

    window.openCatalog = function (companyId, companyName) {
        if (!companyId) {
            window.showToast('error', 'Missing company ID');
            return;
        }

        catalogCompanyId = companyId;
        const nameEl = document.getElementById('catalog_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        currentProductPage = 1;
        loadProducts();
        loadTaxRates();
        loadDiscounts();

        new bootstrap.Modal(document.getElementById('kt_modal_catalog')).show();
    };

    /* ══════════════ PRODUCTS ══════════════ */

    function loadProducts() {
        const loading = document.getElementById('cat_products_loading');
        const empty = document.getElementById('cat_products_empty');
        const container = document.getElementById('cat_products_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        const params = new URLSearchParams({ page: currentProductPage, per_page: 20 });
        const search = document.getElementById('catProductSearch')?.value;
        if (search) params.set('search', search);

        fetch(`/admin/companies/${catalogCompanyId}/products?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                productsList = data.data || [];
                const c = document.getElementById('cat_products_count');
                if (c) c.textContent = data.total ?? productsList.length;

                if (!productsList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderProducts();
                renderProductsPagination(data);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load products');
            });
    }

    function renderProducts() {
        const body = document.getElementById('cat_products_body');
        if (!body) return;
        body.innerHTML = '';

        productsList.forEach(p => {
            const actions = `
                <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editProduct(${p.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteProduct(${p.id}, '${qs(p.name)}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i></button>
            `;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(p.name)}</div>
                    <div class="text-muted fs-8">${h(p.public_id)}</div>
                </td>
                <td class="d-none d-md-table-cell">${p.sku ? `<span class="font-monospace fs-8">${h(p.sku)}</span>` : '—'}</td>
                <td class="d-none d-lg-table-cell"><span class="badge badge-light-${p.mode === 'live' ? 'danger' : 'info'}">${h(p.mode)}</span></td>
                <td><span class="badge badge-light-dark">${p.prices_count ?? 0}</span></td>
                <td><span class="badge badge-light-${p.is_active ? 'success' : 'secondary'}">${p.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    function renderProductsPagination(data) {
        const info = document.getElementById('cat_products_info');
        const el = document.getElementById('cat_products_pager');
        if (!el) return;

        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} products`;

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled) a.onclick = (e) => { e.preventDefault(); currentProductPage = page; loadProducts(); };
            li.appendChild(a);
            el.appendChild(li);
        };

        add(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        add(data.current_page, data.current_page, true);
        add(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.openAddProduct = function () {
        document.getElementById('prod_modal_title').textContent = 'Add Product';
        document.getElementById('prod_id').value = '';
        document.getElementById('prod_company_id').value = catalogCompanyId;
        document.getElementById('productForm').reset();
        document.getElementById('prod_is_active').checked = true;
        new bootstrap.Modal(document.getElementById('kt_modal_add_product')).show();
    };

    window.editProduct = function (id) {
        fetch(`/admin/products/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const p = d.data;
                document.getElementById('prod_modal_title').textContent = 'Edit Product';
                document.getElementById('prod_id').value = p.id;
                document.getElementById('prod_company_id').value = p.company_id;
                document.getElementById('prod_name').value = p.name || '';
                document.getElementById('prod_mode').value = p.mode || 'test';
                document.getElementById('prod_description').value = p.description || '';
                document.getElementById('prod_sku').value = p.sku || '';
                document.getElementById('prod_unit_label').value = p.unit_label || '';
                document.getElementById('prod_tax_code').value = p.tax_code || '';
                document.getElementById('prod_is_active').checked = !!p.is_active;
                document.getElementById('prod_is_shippable').checked = !!p.is_shippable;
                new bootstrap.Modal(document.getElementById('kt_modal_add_product')).show();
            });
    };

    document.getElementById('productForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('prodSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('prod_id').value;
        const companyId = document.getElementById('prod_company_id').value;

        const payload = {
            mode: document.getElementById('prod_mode').value,
            name: document.getElementById('prod_name').value,
            description: document.getElementById('prod_description').value || null,
            sku: document.getElementById('prod_sku').value || null,
            unit_label: document.getElementById('prod_unit_label').value || null,
            tax_code: document.getElementById('prod_tax_code').value || null,
            is_active: document.getElementById('prod_is_active').checked ? 1 : 0,
            is_shippable: document.getElementById('prod_is_shippable').checked ? 1 : 0,
        };

        const url = id ? `/admin/products/${id}` : `/admin/companies/${companyId}/products`;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_product'))?.hide();
                loadProducts();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.deleteProduct = function (id, name) {
        if (!confirm(`Delete "${name}"? Products linked to invoices won't be removed from existing records.`)) return;
        fetch(`/admin/products/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { window.showToast('success', d.message); loadProducts(); }
            else window.showToast('error', d.message);
        });
    };

    document.getElementById('catProductSearch')?.addEventListener('input', function () {
        clearTimeout(window.__catSearchTimer);
        window.__catSearchTimer = setTimeout(() => { currentProductPage = 1; loadProducts(); }, 400);
    });

    /* ══════════════ TAX RATES ══════════════ */

    function loadTaxRates() {
        const loading = document.getElementById('cat_tax_loading');
        const empty = document.getElementById('cat_tax_empty');
        const container = document.getElementById('cat_tax_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${catalogCompanyId}/tax-rates`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                taxRatesList = data.data || [];
                const c = document.getElementById('cat_tax_count');
                if (c) c.textContent = taxRatesList.length;

                if (!taxRatesList.length) { empty.classList.remove('d-none'); return; }
                container.classList.remove('d-none');

                const body = document.getElementById('cat_tax_body');
                body.innerHTML = '';
                taxRatesList.forEach(t => {
                    const actions = `
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editTaxRate(${t.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteTaxRate(${t.id}, '${qs(t.display_name)}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i></button>
                    `;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="fw-bold">${h(t.display_name)}</div>
                            ${t.tax_type ? `<div class="text-muted fs-8 text-uppercase">${h(t.tax_type)}</div>` : ''}
                        </td>
                        <td><span class="badge badge-light-primary">${h(t.percentage)}%</span></td>
                        <td class="d-none d-md-table-cell">${h(t.country_code || '—')}${t.state ? ' / ' + h(t.state) : ''}</td>
                        <td class="d-none d-lg-table-cell"><span class="badge badge-light-${t.mode === 'live' ? 'danger' : 'info'}">${h(t.mode)}</span></td>
                        <td><span class="badge badge-light-${t.is_active ? 'success' : 'secondary'}">${t.is_active ? 'Active' : 'Off'}</span></td>
                        <td class="text-end">${actions}</td>
                    `;
                    body.appendChild(tr);
                });
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load tax rates');
            });
    }

    window.openAddTaxRate = function () {
        document.getElementById('tax_modal_title').textContent = 'Add Tax Rate';
        document.getElementById('tax_id').value = '';
        document.getElementById('tax_company_id').value = catalogCompanyId;
        document.getElementById('taxRateForm').reset();
        document.getElementById('tax_is_active').checked = true;
        new bootstrap.Modal(document.getElementById('kt_modal_add_tax_rate')).show();
    };

    window.editTaxRate = function (id) {
        const t = taxRatesList.find(x => x.id === id);
        if (!t) return;

        document.getElementById('tax_modal_title').textContent = 'Edit Tax Rate';
        document.getElementById('tax_id').value = t.id;
        document.getElementById('tax_company_id').value = t.company_id;
        document.getElementById('tax_display_name').value = t.display_name || '';
        document.getElementById('tax_mode').value = t.mode || 'test';
        document.getElementById('tax_percentage').value = t.percentage || '';
        document.getElementById('tax_country_code').value = t.country_code || '';
        document.getElementById('tax_type').value = t.tax_type || '';
        document.getElementById('tax_is_inclusive').checked = !!t.is_inclusive;
        document.getElementById('tax_is_active').checked = !!t.is_active;

        new bootstrap.Modal(document.getElementById('kt_modal_add_tax_rate')).show();
    };

    document.getElementById('taxRateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('taxSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('tax_id').value;
        const companyId = document.getElementById('tax_company_id').value;

        const payload = {
            mode: document.getElementById('tax_mode').value,
            display_name: document.getElementById('tax_display_name').value,
            percentage: document.getElementById('tax_percentage').value,
            country_code: document.getElementById('tax_country_code').value || null,
            tax_type: document.getElementById('tax_type').value || null,
            is_inclusive: document.getElementById('tax_is_inclusive').checked ? 1 : 0,
            is_active: document.getElementById('tax_is_active').checked ? 1 : 0,
        };

        const url = id ? `/admin/tax-rates/${id}` : `/admin/companies/${companyId}/tax-rates`;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_tax_rate'))?.hide();
                loadTaxRates();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.deleteTaxRate = function (id, name) {
        if (!confirm(`Delete tax rate "${name}"?`)) return;
        fetch(`/admin/tax-rates/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { window.showToast('success', d.message); loadTaxRates(); }
            else window.showToast('error', d.message);
        });
    };

    /* ══════════════ DISCOUNTS ══════════════ */

    function loadDiscounts() {
        const loading = document.getElementById('cat_discounts_loading');
        const empty = document.getElementById('cat_discounts_empty');
        const container = document.getElementById('cat_discounts_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${catalogCompanyId}/discounts`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                discountsList = data.data || [];
                const c = document.getElementById('cat_discounts_count');
                if (c) c.textContent = discountsList.length;

                if (!discountsList.length) { empty.classList.remove('d-none'); return; }
                container.classList.remove('d-none');

                const body = document.getElementById('cat_discounts_body');
                body.innerHTML = '';
                discountsList.forEach(d => {
                    const actions = `
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editDiscount(${d.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteDiscount(${d.id}, '${qs(d.code || d.name || 'discount')}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i></button>
                    `;
                    const redemptions = d.max_redemptions
                        ? `${d.times_redeemed} / ${d.max_redemptions}`
                        : `${d.times_redeemed}`;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="fw-bold font-monospace">${h(d.code || '—')}</div>
                            ${d.name ? `<div class="text-muted fs-8">${h(d.name)}</div>` : ''}
                        </td>
                        <td><span class="badge badge-light-primary">${h(d.label)}</span></td>
                        <td class="d-none d-md-table-cell">${h(redemptions)}</td>
                        <td class="d-none d-lg-table-cell">${h(d.expires_at || '—')}</td>
                        <td><span class="badge badge-light-${d.is_valid ? 'success' : 'secondary'}">${d.is_valid ? 'Active' : 'Inactive'}</span></td>
                        <td class="text-end">${actions}</td>
                    `;
                    body.appendChild(tr);
                });
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load discounts');
            });
    }

    window.openAddDiscount = function () {
        document.getElementById('disc_modal_title').textContent = 'Add Discount';
        document.getElementById('disc_id').value = '';
        document.getElementById('disc_company_id').value = catalogCompanyId;
        document.getElementById('discountForm').reset();
        document.getElementById('disc_is_active').checked = true;
        toggleDiscountTypeFields();
        new bootstrap.Modal(document.getElementById('kt_modal_add_discount')).show();
    };

    window.editDiscount = function (id) {
        const d = discountsList.find(x => x.id === id);
        if (!d) return;

        document.getElementById('disc_modal_title').textContent = 'Edit Discount';
        document.getElementById('disc_id').value = d.id;
        document.getElementById('disc_company_id').value = d.company_id;
        document.getElementById('disc_code').value = d.code || '';
        document.getElementById('disc_name').value = d.name || '';
        document.getElementById('disc_mode').value = d.mode || 'test';
        document.getElementById('disc_type').value = d.type || 'percentage';
        document.getElementById('disc_percent_off').value = d.percent_off || '';
        document.getElementById('disc_amount_off').value = d.amount_off || '';
        document.getElementById('disc_currency').value = d.currency || '';
        document.getElementById('disc_duration').value = d.duration || 'once';
        document.getElementById('disc_max_redemptions').value = d.max_redemptions || '';
        document.getElementById('disc_minimum_order_amount').value = d.minimum_order_amount || '';
        document.getElementById('disc_is_active').checked = !!d.is_active;

        toggleDiscountTypeFields();
        new bootstrap.Modal(document.getElementById('kt_modal_add_discount')).show();
    };

    window.toggleDiscountTypeFields = function () {
        const type = document.getElementById('disc_type')?.value;
        document.querySelectorAll('.disc-percentage').forEach(el => el.classList.toggle('d-none', type !== 'percentage'));
        document.querySelectorAll('.disc-fixed').forEach(el => el.classList.toggle('d-none', type !== 'fixed_amount'));
    };

    document.getElementById('discountForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('discSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('disc_id').value;
        const companyId = document.getElementById('disc_company_id').value;

        const payload = {
            mode: document.getElementById('disc_mode').value,
            code: document.getElementById('disc_code').value || null,
            name: document.getElementById('disc_name').value || null,
            type: document.getElementById('disc_type').value,
            percent_off: document.getElementById('disc_percent_off').value || null,
            amount_off: document.getElementById('disc_amount_off').value || null,
            currency: document.getElementById('disc_currency').value || null,
            duration: document.getElementById('disc_duration').value,
            max_redemptions: document.getElementById('disc_max_redemptions').value || null,
            minimum_order_amount: document.getElementById('disc_minimum_order_amount').value || null,
            is_active: document.getElementById('disc_is_active').checked ? 1 : 0,
        };

        const url = id ? `/admin/discounts/${id}` : `/admin/companies/${companyId}/discounts`;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_discount'))?.hide();
                loadDiscounts();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.deleteDiscount = function (id, label) {
        if (!confirm(`Delete discount "${label}"?`)) return;
        fetch(`/admin/discounts/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { window.showToast('success', d.message); loadDiscounts(); }
            else window.showToast('error', d.message);
        });
    };

})();
</script>