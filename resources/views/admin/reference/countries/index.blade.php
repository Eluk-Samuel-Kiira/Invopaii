@extends('layouts.admin')

@section('title', 'Countries')
@section('page_title', 'Countries')

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
    <li class="breadcrumb-item text-muted">Countries</li>
@endsection

@section('content')
    <div class="card card-flush">
        {{-- Card header --}}
        <div class="card-header mt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1 me-5">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Countries" />
                </div>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_country">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i> Add Country
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            {{-- Loading --}}
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading countries...</p>
            </div>

            {{-- Table --}}
            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-200px">Country</th>
                                <th class="min-w-120px">Codes</th>
                                <th class="min-w-100px">Currency</th>
                                <th class="min-w-150px">Region</th>
                                <th class="min-w-200px">Market Flags</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="countriesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            {{-- No data --}}
            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No countries found.</p>
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

    {{-- Add Country Modal --}}
    <div class="modal fade" id="kt_modal_add_country" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Country</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="addCountryForm">
                        @csrf
                        @include('admin.reference.countries._form', ['prefix' => 'add'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addCountryBtn">
                                <span class="indicator-label">Create Country</span>
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

    {{-- Edit Country Modal --}}
    <div class="modal fade" id="kt_modal_edit_country" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Country</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="editCountryForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="country_id" id="edit_country_id">
                        @include('admin.reference.countries._form', ['prefix' => 'edit'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editCountryBtn">
                                <span class="indicator-label">Update Country</span>
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

    const PAYMENT_METHODS = ['card', 'mobile_money', 'bank_transfer', 'cash', 'crypto'];
    const DOC_TYPES = ['certificate_of_incorporation', 'tax_id', 'directors_id', 'proof_of_address', 'bank_statement'];

    function formatLabel(str) {
        if (!str) return '';
        return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function statusBadge(country) {
        if (country.is_sanctioned) return '<span class="badge badge-light-danger">Sanctioned</span>';
        if (!country.is_supported) return '<span class="badge badge-light-secondary">Unsupported</span>';
        if (country.is_high_risk) return '<span class="badge badge-light-warning">High Risk</span>';
        return '<span class="badge badge-light-success">Active</span>';
    }

    function flagChip(label, active, field, id, tone) {
        const on = active
            ? `badge-light-${tone}`
            : 'badge-light-secondary';
        return `<span class="badge ${on} cursor-pointer me-1 mb-1" onclick="toggleFlag(${id}, '${field}')" title="Toggle ${label}">${label}</span>`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadCountries();

        const searchInput = document.getElementById('searchInput');
        let timeout;
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCountries();
            }, 500);
        });
    });

    function loadCountries() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        let url = `{{ route("countries.data") }}?page=${currentPage}&per_page=20`;
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
                window.showToast('error', 'Failed to load countries');
                console.error(err);
            });
    }

    function renderTable(countries) {
        const tbody = document.getElementById('countriesTableBody');
        tbody.innerHTML = '';

        countries.forEach(c => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `<span class="fw-bold">${c.id}</span>`;

            row.insertCell(1).innerHTML = `
                <div class="d-flex align-items-center">
                    <span style="font-size:24px; margin-right:10px;">${c.flag_emoji ?? '🏳️'}</span>
                    <div>
                        <div class="fw-bold text-gray-800">${escapeHtml(c.name)}</div>
                        ${c.official_name ? `<div class="text-muted fs-7">${escapeHtml(c.official_name)}</div>` : ''}
                    </div>
                </div>
            `;

            row.insertCell(2).innerHTML = `
                <div><span class="badge badge-light-dark me-1">${escapeHtml(c.iso2 ?? '')}</span>
                <span class="badge badge-light-dark">${escapeHtml(c.iso3 ?? '')}</span></div>
                ${c.phone_code ? `<div class="text-muted fs-7 mt-1">+${escapeHtml(c.phone_code)}</div>` : ''}
            `;

            row.insertCell(3).innerHTML = c.default_currency
                ? `<span class="badge badge-light-primary">${escapeHtml(c.default_currency)}</span>`
                : '<span class="text-muted">—</span>';

            row.insertCell(4).innerHTML = `
                <div>${escapeHtml(c.region ?? '—')}</div>
                ${c.subregion ? `<div class="text-muted fs-7">${escapeHtml(c.subregion)}</div>` : ''}
            `;

            row.insertCell(5).innerHTML = `
                ${flagChip('Supported', c.is_supported, 'is_supported', c.id, 'success')}
                ${flagChip('Collections', c.collections_enabled, 'collections_enabled', c.id, 'info')}
                ${flagChip('Payouts', c.payouts_enabled, 'payouts_enabled', c.id, 'primary')}
                ${flagChip('High Risk', c.is_high_risk, 'is_high_risk', c.id, 'warning')}
                ${flagChip('Sanctioned', c.is_sanctioned, 'is_sanctioned', c.id, 'danger')}
            `;

            row.insertCell(6).innerHTML = statusBadge(c);

            row.insertCell(7).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editCountry(${c.id})" title="Edit" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-setting-3 fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteCountry(${c.id}, '${escapeHtml(c.name)}')" title="Delete" style="width:32px;height:32px;">
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
            if (!isDisabled) {
                a.onclick = (e) => { e.preventDefault(); changePage(page); };
            }
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
            loadCountries();
        }
    };

    window.toggleFlag = function (id, field) {
        fetch(`/admin/countries/${id}/toggle-flag`, {
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
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to toggle flag'));
    };

    window.editCountry = function (id) {
        fetch(`/admin/countries/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                const c = data.data;

                document.getElementById('edit_country_id').value = c.id;
                document.getElementById('edit_iso2').value = c.iso2 ?? '';
                document.getElementById('edit_iso3').value = c.iso3 ?? '';
                document.getElementById('edit_name').value = c.name ?? '';
                document.getElementById('edit_official_name').value = c.official_name ?? '';
                document.getElementById('edit_phone_code').value = c.phone_code ?? '';
                document.getElementById('edit_default_currency').value = c.default_currency ?? '';
                document.getElementById('edit_region').value = c.region ?? '';
                document.getElementById('edit_subregion').value = c.subregion ?? '';
                document.getElementById('edit_flag_emoji').value = c.flag_emoji ?? '';

                ['is_supported','collections_enabled','payouts_enabled','is_high_risk','is_sanctioned'].forEach(f => {
                    const el = document.getElementById(`edit_${f}`);
                    if (el) el.checked = !!c[f];
                });

                // multi-selects
                setMultiSelect('edit_supported_payment_methods', c.supported_payment_methods || []);
                setMultiSelect('edit_required_business_documents', c.required_business_documents || []);

                new bootstrap.Modal(document.getElementById('kt_modal_edit_country')).show();
            });
    };

    function setMultiSelect(id, values) {
        const el = document.getElementById(id);
        if (!el) return;
        Array.from(el.options).forEach(opt => {
            opt.selected = values.includes(opt.value);
        });
    }

    window.deleteCountry = function (id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        fetch(`/admin/countries/${id}`, {
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
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    document.getElementById('addCountryForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('addCountryBtn');
        window.showButtonSpinner(btn);

        fetch('{{ route("countries.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_country'))?.hide();
                this.reset();
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to create country'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    document.getElementById('editCountryForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('editCountryBtn');
        window.showButtonSpinner(btn);
        const id = document.getElementById('edit_country_id').value;

        fetch(`/admin/countries/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_country'))?.hide();
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to update country'))
        .finally(() => window.hideButtonSpinner(btn));
    });
</script>
@endpush