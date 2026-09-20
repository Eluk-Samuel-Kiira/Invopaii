@extends('layouts.admin')

@section('title', 'Currencies')
@section('page_title', 'Currencies')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Reference Data</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Currencies</li>
@endsection

@section('content')
    <div class="card card-flush">
        {{-- Card header --}}
        <div class="card-header mt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1 me-5">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Currencies" />
                </div>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_currency">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i> Add Currency
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            {{-- Loading --}}
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading currencies...</p>
            </div>

            {{-- Table --}}
            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-150px">Currency</th>
                                <th class="min-w-80px">Symbol</th>
                                <th class="min-w-120px">Decimals</th>
                                <th class="min-w-180px">Charge Limits</th>
                                <th class="min-w-220px">Capabilities</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="currenciesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            {{-- No data --}}
            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No currencies found.</p>
            </div>

            {{-- Pagination --}}
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav>
                    <ul class="pagination m-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- Add Currency Modal --}}
    <div class="modal fade" id="kt_modal_add_currency" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Currency</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="addCurrencyForm">
                        @csrf
                        @include('admin.reference.currencies._form', ['prefix' => 'add'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addCurrencyBtn">
                                <span class="indicator-label">Create Currency</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Currency Modal --}}
    <div class="modal fade" id="kt_modal_edit_currency" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Currency</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="editCurrencyForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="currency_id" id="edit_currency_id">
                        @include('admin.reference.currencies._form', ['prefix' => 'edit'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editCurrencyBtn">
                                <span class="indicator-label">Update Currency</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let currentSearch = '';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function statusBadge(c) {
        if (!c.is_active) return '<span class="badge badge-light-secondary">Inactive</span>';
        if (c.is_settlement_currency && c.is_presentment_currency) return '<span class="badge badge-light-success">Full</span>';
        if (c.is_settlement_currency) return '<span class="badge badge-light-primary">Settlement</span>';
        if (c.is_presentment_currency) return '<span class="badge badge-light-info">Presentment</span>';
        return '<span class="badge badge-light-warning">Limited</span>';
    }

    function flagChip(label, active, field, id, tone) {
        const on = active ? `badge-light-${tone}` : 'badge-light-secondary';
        return `<span class="badge ${on} cursor-pointer me-1 mb-1" onclick="toggleFlag(${id}, '${field}')" title="Toggle ${label}">${label}</span>`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadCurrencies();

        const searchInput = document.getElementById('searchInput');
        let timeout;
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCurrencies();
            }, 500);
        });
    });

    function loadCurrencies() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        let url = `{{ route("currencies.data") }}?page=${currentPage}&per_page=20`;
        if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;

        fetch(url)
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
            .catch(err => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load currencies');
                console.error(err);
            });
    }

    function renderTable(currencies) {
        const tbody = document.getElementById('currenciesTableBody');
        tbody.innerHTML = '';

        currencies.forEach(c => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `<span class="fw-bold">${c.id}</span>`;

            row.insertCell(1).innerHTML = `
                <div class="d-flex align-items-center">
                    
                    <div>
                        <div class="fw-bold text-gray-800">${escapeHtml(c.name)}</div>
                        <div class="text-muted fs-7">${escapeHtml(c.code)}</div>
                    </div>
                </div>
            `;

            row.insertCell(2).innerHTML = c.symbol
                ? `<span class="fs-3">${escapeHtml(c.symbol)}</span>`
                : '<span class="text-muted">—</span>';

            row.insertCell(3).innerHTML = c.is_zero_decimal
                ? `<span class="badge badge-light-warning">0 (zero-decimal)</span>`
                : `<span class="badge badge-light-primary">${c.exponent}</span> <span class="text-muted fs-7">${escapeHtml(c.minor_unit_label)}</span>`;

            row.insertCell(4).innerHTML = `
                <div class="fs-7">
                    <div><span class="text-muted">Min:</span> <span class="fw-semibold">${c.min_charge_display ?? '—'}</span></div>
                    <div><span class="text-muted">Max:</span> <span class="fw-semibold">${c.max_charge_display ?? '—'}</span></div>
                </div>
            `;

            row.insertCell(5).innerHTML = `
                ${flagChip('Active', c.is_active, 'is_active', c.id, 'success')}
                ${flagChip('Settlement', c.is_settlement_currency, 'is_settlement_currency', c.id, 'primary')}
                ${flagChip('Presentment', c.is_presentment_currency, 'is_presentment_currency', c.id, 'info')}
                ${flagChip('Zero-decimal', c.is_zero_decimal, 'is_zero_decimal', c.id, 'warning')}
            `;

            row.insertCell(6).innerHTML = statusBadge(c);

            row.insertCell(7).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editCurrency(${c.id})" title="Edit" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-setting-3 fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteCurrency(${c.id}, '${escapeHtml(c.code)}')" title="Delete" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-trash fs-3 text-danger">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span>
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
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;

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
            loadCurrencies();
        }
    };

    window.toggleFlag = function (id, field) {
        fetch(`/admin/currencies/${id}/toggle-flag`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ field })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCurrencies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to toggle flag'));
    };

    window.editCurrency = function (id) {
        fetch(`/admin/currencies/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                const c = data.data;

                document.getElementById('edit_currency_id').value = c.id;
                document.getElementById('edit_code').value = c.code ?? '';
                document.getElementById('edit_name').value = c.name ?? '';
                document.getElementById('edit_symbol').value = c.symbol ?? '';
                document.getElementById('edit_exponent').value = c.exponent ?? 2;
                document.getElementById('edit_min_charge_amount').value = c.min_charge_amount ?? '';
                document.getElementById('edit_max_charge_amount').value = c.max_charge_amount ?? '';

                ['is_zero_decimal','is_active','is_settlement_currency','is_presentment_currency'].forEach(f => {
                    const el = document.getElementById(`edit_${f}`);
                    if (el) el.checked = !!c[f];
                });

                new bootstrap.Modal(document.getElementById('kt_modal_edit_currency')).show();
            });
    };

    window.deleteCurrency = function (id, code) {
        if (!confirm(`Delete "${code}"? This cannot be undone.`)) return;
        fetch(`/admin/currencies/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCurrencies();
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    document.getElementById('addCurrencyForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('addCurrencyBtn');
        window.showButtonSpinner(btn);

        fetch('{{ route("currencies.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_currency'))?.hide();
                this.reset();
                loadCurrencies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to create currency'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    document.getElementById('editCurrencyForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('editCurrencyBtn');
        window.showButtonSpinner(btn);
        const id = document.getElementById('edit_currency_id').value;

        fetch(`/admin/currencies/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_currency'))?.hide();
                loadCurrencies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to update currency'))
        .finally(() => window.hideButtonSpinner(btn));
    });
</script>
@endpush