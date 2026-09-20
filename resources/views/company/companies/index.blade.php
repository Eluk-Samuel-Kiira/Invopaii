@extends('layouts.admin')

@section('title', 'Companies')
@section('page_title', 'Companies')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Platform</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Companies</li>
@endsection

@section('content')
    <div class="card card-flush">
        <div class="card-header mt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1 me-5">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search companies" />
                </div>
                <div class="d-flex align-items-center my-1 me-3">
                    <select id="statusFilter" class="form-select form-select-solid w-150px">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_review">In Review</option>
                        <option value="active">Active</option>
                        <option value="restricted">Restricted</option>
                        <option value="suspended">Suspended</option>
                        <option value="rejected">Rejected</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="d-flex align-items-center my-1">
                    <select id="kybFilter" class="form-select form-select-solid w-150px">
                        <option value="">All KYB</option>
                        <option value="unverified">Unverified</option>
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="card-toolbar">
                @can('view companies')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_company">
                        <i class="ki-duotone ki-plus-square fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i> Add Company
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading companies...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-220px">Company</th>
                                <th class="min-w-150px">Country</th>
                                <th class="min-w-120px">Currency</th>
                                <th class="min-w-120px">Status</th>
                                <th class="min-w-120px">KYB</th>
                                <th class="min-w-180px">Mode</th>
                                <th class="min-w-100px">Created</th>
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

            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Add Company Modal --}}
    <div class="modal fade" id="kt_modal_add_company" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Company</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="addCompanyForm">
                        @csrf
                        @include('company.companies._form', ['prefix' => 'add'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addCompanyBtn">
                                <span class="indicator-label">Create Company</span>
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

    {{-- Edit Company Modal --}}
    <div class="modal fade" id="kt_modal_edit_company" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Company</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="editCompanyForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="company_id" id="edit_company_id">
                        @include('company.companies._form', ['prefix' => 'edit'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editCompanyBtn">
                                <span class="indicator-label">Update Company</span>
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

    {{-- Change Status Modal --}}
    <div class="modal fade" id="kt_modal_change_status" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Change Company Status</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <input type="hidden" id="status_company_id">
                    <input type="hidden" id="status_current_value">

                    {{-- Company identity block --}}
                    <div class="alert alert-info d-flex align-items-center p-4 mb-7">
                        <i class="ki-duotone ki-briefcase fs-2tx me-3">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <div>
                            <strong id="status_company_name">—</strong><br>
                            <span class="text-muted fs-7" id="status_company_current">Current: —</span>
                        </div>
                    </div>

                    {{-- Status dropdown --}}
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">New Status</label>
                        <select class="form-select form-select-solid" id="status_new_value">
                            <option value="pending">Pending</option>
                            <option value="in_review">In Review</option>
                            <option value="active">Active</option>
                            <option value="restricted">Restricted</option>
                            <option value="suspended">Suspended</option>
                            <option value="rejected">Rejected</option>
                            <option value="closed">Closed</option>
                        </select>
                        <div class="text-muted fs-7 mt-1" id="status_hint"></div>
                    </div>

                    {{-- Reason — shown only for destructive statuses --}}
                    <div class="fv-row mb-7 d-none" id="status_reason_wrapper">
                        <label class="fw-semibold fs-6 mb-2">Reason</label>
                        <textarea class="form-control form-control-solid" id="status_reason" rows="3"
                                placeholder="Why is this status being applied?"></textarea>
                        <div class="text-muted fs-7 mt-1">Recommended for suspended / rejected / closed.</div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="statusConfirmBtn">
                            <span class="indicator-label">Change Status</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let currentSearch = '';
    let currentStatus = '';
    let currentKyb = '';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function flagChip(label, active, field, id, tone) {
        const on = active ? `badge-light-${tone}` : 'badge-light-secondary';
        return `<span class="badge ${on} cursor-pointer me-1 mb-1" onclick="toggleFlag(${id}, '${field}')" title="Toggle ${label}">${label}</span>`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadCompanies();

        const searchInput = document.getElementById('searchInput');
        let timeout;
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCompanies();
            }, 500);
        });

        document.getElementById('statusFilter')?.addEventListener('change', function () {
            currentStatus = this.value;
            currentPage = 1;
            loadCompanies();
        });

        document.getElementById('kybFilter')?.addEventListener('change', function () {
            currentKyb = this.value;
            currentPage = 1;
            loadCompanies();
        });
    });

    function loadCompanies() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        let url = `{{ route("admin.companies.data") }}?page=${currentPage}&per_page=20`;
        if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
        if (currentStatus) url += `&status=${currentStatus}`;
        if (currentKyb) url += `&kyb_status=${currentKyb}`;

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
                window.showToast('error', 'Failed to load companies');
                console.error(err);
            });
    }

    function renderTable(companies) {
        const tbody = document.getElementById('companiesTableBody');
        tbody.innerHTML = '';

        companies.forEach(c => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `<span class="fw-bold">${c.id}</span>`;

            row.insertCell(1).innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3" style="background:${c.brand_color || '#f1f1f4'};">
                        <span class="text-white fw-bold">${escapeHtml((c.name || '?').charAt(0).toUpperCase())}</span>
                    </div>
                    <div>
                        <a href="#" class="fw-bold text-gray-800 text-hover-primary" onclick="editCompany(${c.id}); return false;">
                            ${escapeHtml(c.name)}
                        </a>
                        <div class="text-muted fs-7">${escapeHtml(c.public_id)}</div>
                    </div>
                </div>
            `;

            row.insertCell(2).innerHTML = c.country ? `
                <div>${c.country.flag_emoji ?? ''} ${escapeHtml(c.country.name)}</div>
                <div class="text-muted fs-7">${escapeHtml(c.country.iso2 ?? '')}</div>
            ` : '<span class="text-muted">—</span>';

            row.insertCell(3).innerHTML = `
                <div><span class="badge badge-light-primary">${escapeHtml(c.default_currency)}</span></div>
                ${c.settlement_currency ? `<div class="text-muted fs-7 mt-1">→ ${escapeHtml(c.settlement_currency)}</div>` : ''}
            `;

            row.insertCell(4).innerHTML = `
                <span class="badge badge-light-${c.status_badge.tone}">${escapeHtml(c.status_badge.label)}</span>
            `;

            row.insertCell(5).innerHTML = `
                <span class="badge badge-light-${c.kyb_badge.tone}">${escapeHtml(c.kyb_badge.label)}</span>
            `;

            row.insertCell(6).innerHTML = `
                ${flagChip('Live', c.live_mode_enabled, 'live_mode_enabled', c.id, 'danger')}
                ${flagChip('Charges', c.charges_enabled, 'charges_enabled', c.id, 'success')}
                ${flagChip('Payouts', c.payouts_enabled, 'payouts_enabled', c.id, 'primary')}
            `;

            row.insertCell(7).innerHTML = `<span class="text-muted">${escapeHtml(c.created_at)}</span>`;

            row.insertCell(8).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editCompany(${c.id})" title="Edit" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-setting-3 fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light"
                        onclick="changeStatus(${c.id}, '${c.status}', '${escapeHtml(c.name).replace(/'/g, "\\'")}')"
                        title="Change Status" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-switch fs-3 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteCompany(${c.id}, '${escapeHtml(c.name)}')" title="Delete" style="width:32px;height:32px;">
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
            loadCompanies();
        }
    };

    window.toggleFlag = function (id, field) {
        fetch(`/admin/companies/${id}/toggle-flag`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ field })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCompanies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to toggle flag'));
    };

    const STATUS_META = {
        pending:    { label: 'Pending',    hint: 'Awaiting KYB submission.' },
        in_review:  { label: 'In Review',  hint: 'Compliance is reviewing submitted documents.' },
        active:     { label: 'Active',     hint: 'Requires KYB verified. Enables charges & payouts automatically.' },
        restricted: { label: 'Restricted', hint: 'Charges allowed but no new payouts.' },
        suspended:  { label: 'Suspended',  hint: 'Disables live mode, charges and payouts.' },
        rejected:   { label: 'Rejected',   hint: 'Terminal. Company cannot re-apply from this state.' },
        closed:     { label: 'Closed',     hint: 'Terminal. Account is shut down.' },
    };

    const DESTRUCTIVE_STATUSES = ['suspended', 'rejected', 'closed'];

    window.changeStatus = function (id, currentStatus, companyName) {
        document.getElementById('status_company_id').value = id;
        document.getElementById('status_current_value').value = currentStatus;
        document.getElementById('status_company_name').textContent = companyName || 'Company';
        document.getElementById('status_company_current').textContent =
            `Current: ${STATUS_META[currentStatus]?.label ?? currentStatus}`;

        const select = document.getElementById('status_new_value');
        select.value = currentStatus;

        updateStatusModal();
        new bootstrap.Modal(document.getElementById('kt_modal_change_status')).show();
    };

    function updateStatusModal() {
        const select = document.getElementById('status_new_value');
        const val = select.value;
        const current = document.getElementById('status_current_value').value;
        const hint = document.getElementById('status_hint');
        const reasonWrapper = document.getElementById('status_reason_wrapper');
        const confirmBtn = document.getElementById('statusConfirmBtn');

        hint.textContent = STATUS_META[val]?.hint ?? '';

        // Show reason field only for destructive statuses
        if (DESTRUCTIVE_STATUSES.includes(val)) {
            reasonWrapper.classList.remove('d-none');
        } else {
            reasonWrapper.classList.add('d-none');
            document.getElementById('status_reason').value = '';
        }

        // Disable confirm if unchanged
        confirmBtn.disabled = (val === current);
    }

    document.getElementById('status_new_value')?.addEventListener('change', updateStatusModal);

    document.getElementById('statusConfirmBtn')?.addEventListener('click', function () {
        const id = document.getElementById('status_company_id').value;
        const status = document.getElementById('status_new_value').value;
        const current = document.getElementById('status_current_value').value;
        const reason = document.getElementById('status_reason').value || null;

        if (status === current) {
            window.showToast('warning', 'Status is unchanged.');
            return;
        }

        const btn = this;
        window.showButtonSpinner(btn);

        fetch(`/admin/companies/${id}/change-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status, reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_change_status'))?.hide();
                loadCompanies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to change status'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.editCompany = function (id) {
        fetch(`/admin/companies/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                const c = data.data;

                const set = (field, val) => {
                    const el = document.getElementById(`edit_${field}`);
                    if (el) el.value = val ?? '';
                };

                document.getElementById('edit_company_id').value = c.id;
                ['name','legal_name','slug','email','support_email','support_phone','website','brand_color',
                 'country_id','business_type','industry','mcc','registration_number','tax_identification_number',
                 'incorporated_on','address_line1','address_line2','city','state','postal_code',
                 'default_currency','settlement_currency','timezone','statement_descriptor',
                 'risk_level','payout_schedule','payout_delay_days','reserve_percent','reserve_hold_days','owner_id']
                    .forEach(f => set(f, c[f]));

                new bootstrap.Modal(document.getElementById('kt_modal_edit_company')).show();
            });
    };

    window.deleteCompany = function (id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        fetch(`/admin/companies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCompanies();
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    // ── Form options loader ──
    function loadFormOptions() {
        fetch('{{ route("admin.companies.form-options") }}')
            .then(res => res.json())
            .then(data => {
                ['add', 'edit'].forEach(p => {
                    const country = document.getElementById(`${p}_country_id`);
                    if (country) {
                        country.innerHTML = '<option value="">Select country</option>' +
                            data.countries.map(c => `<option value="${c.id}" data-currency="${c.currency || ''}">${escapeHtml(c.label)}</option>`).join('');
                    }
                    const owner = document.getElementById(`${p}_owner_id`);
                    if (owner) {
                        owner.innerHTML = '<option value="">Select owner</option>' +
                            data.users.map(u => `<option value="${u.id}">${escapeHtml(u.label)}</option>`).join('');
                    }
                    const bt = document.getElementById(`${p}_business_type`);
                    if (bt) {
                        bt.innerHTML = data.business_types.map(b => `<option value="${b}">${b.charAt(0).toUpperCase() + b.slice(1)}</option>`).join('');
                    }
                    const ps = document.getElementById(`${p}_payout_schedule`);
                    if (ps) {
                        ps.innerHTML = data.payout_schedules.map(s => `<option value="${s}">${s.charAt(0).toUpperCase() + s.slice(1)}</option>`).join('');
                    }
                    const rl = document.getElementById(`${p}_risk_level`);
                    if (rl) {
                        rl.innerHTML = data.risk_levels.map(l => `<option value="${l}">${l.charAt(0).toUpperCase() + l.slice(1)}</option>`).join('');
                    }
                });
            });
    }
    document.addEventListener('DOMContentLoaded', loadFormOptions);

    // Auto-fill default currency when country changes
    ['add', 'edit'].forEach(p => {
        document.getElementById(`${p}_country_id`)?.addEventListener('change', function () {
            const cur = this.options[this.selectedIndex]?.dataset.currency;
            if (cur) document.getElementById(`${p}_default_currency`).value = cur;
        });
    });

    document.getElementById('addCompanyForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('addCompanyBtn');
        window.showButtonSpinner(btn);

        fetch('{{ route("admin.companies.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_company'))?.hide();
                this.reset();
                loadCompanies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to create company'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    document.getElementById('editCompanyForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('editCompanyBtn');
        window.showButtonSpinner(btn);
        const id = document.getElementById('edit_company_id').value;

        fetch(`/admin/companies/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_company'))?.hide();
                loadCompanies();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to update company'))
        .finally(() => window.hideButtonSpinner(btn));
    });
</script>
@endpush