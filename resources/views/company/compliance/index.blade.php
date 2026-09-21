@extends('layouts.admin')

@section('title', 'Verification Queue')
@section('page_title', 'Verification Queue')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Platform</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Verification Queue</li>
@endsection

@section('content')

    {{-- Stats row --}}
    <div class="row g-5 mb-5" id="statsRow">
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Pending</div>
                    <div class="fs-2 fw-bold text-warning" id="stat_pending">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Unverified</div>
                    <div class="fs-2 fw-bold text-danger" id="stat_unverified">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Verified</div>
                    <div class="fs-2 fw-bold text-success" id="stat_verified">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Rejected</div>
                    <div class="fs-2 fw-bold text-secondary" id="stat_rejected">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Docs Pending</div>
                    <div class="fs-2 fw-bold text-info" id="stat_docs">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-bold text-uppercase mb-1">In Review</div>
                    <div class="fs-2 fw-bold text-primary" id="stat_in_review">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100">
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 gap-sm-3 my-1 w-100">

                    {{-- Search --}}
                    <div class="position-relative w-100 w-sm-auto flex-grow-1" style="max-width:280px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid ps-13 w-100" placeholder="Search companies" />
                    </div>

                    {{-- Status --}}
                    <select id="statusFilter" class="form-select form-select-solid w-100 w-sm-auto" style="max-width:220px;">
                        <option value="pending" selected>Pending + Unverified</option>
                        <option value="in_review">In Review</option>
                        <option value="all">All Companies</option>
                    </select>

                </div>
            </div>

            <div class="card-toolbar m-0">
                <button type="button" class="btn btn-light-primary w-100 w-sm-auto" onclick="refreshQueue()">
                    <i class="ki-duotone ki-arrows-circle fs-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span class="ms-1">Refresh</span>
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading queue...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-220px">Company</th>
                                <th class="min-w-150px">Country</th>
                                <th class="min-w-120px">KYB</th>
                                <th class="min-w-120px">Status</th>
                                <th class="min-w-150px">Documents</th>
                                <th class="min-w-100px">Reps</th>
                                <th class="min-w-100px">Age</th>
                                <th class="text-end min-w-150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="queueTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No companies awaiting verification.</p>
            </div>

            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Compliance modal will be reused from companies index --}}
    @include('company.companies._compliance_modals')

@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let currentSearch = '';
    let currentStatus = 'pending';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadStats();
        loadQueue();

        const searchInput = document.getElementById('searchInput');
        let timeout;
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadQueue();
            }, 500);
        });

        document.getElementById('statusFilter')?.addEventListener('change', function () {
            currentStatus = this.value;
            currentPage = 1;
            loadQueue();
        });
    });

    function refreshQueue() {
        loadStats();
        loadQueue();
    }

    function loadStats() {
        fetch('{{ route("admin.compliance.stats") }}')
            .then(res => res.json())
            .then(s => {
                document.getElementById('stat_pending').textContent = s.pending_verification;
                document.getElementById('stat_unverified').textContent = s.unverified;
                document.getElementById('stat_verified').textContent = s.verified;
                document.getElementById('stat_rejected').textContent = s.rejected;
                document.getElementById('stat_docs').textContent = s.pending_documents;
                document.getElementById('stat_in_review').textContent = s.pending_review_companies;
            })
            .catch(() => {});
    }

    function loadQueue() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        let url = `{{ route("admin.compliance.data") }}?page=${currentPage}&per_page=20&status=${currentStatus}`;
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
                renderQueue(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load queue');
            });
    }

    function renderQueue(companies) {
        const tbody = document.getElementById('queueTableBody');
        tbody.innerHTML = '';

        companies.forEach(c => {
            const tr = tbody.insertRow();

            tr.insertCell(0).innerHTML = `<span class="fw-bold">${c.id}</span>`;

            tr.insertCell(1).innerHTML = `
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

            tr.insertCell(2).innerHTML = c.country
                ? `${c.country.flag_emoji ?? ''} ${escapeHtml(c.country.name)}`
                : '<span class="text-muted">—</span>';

            tr.insertCell(3).innerHTML = `<span class="badge badge-light-${c.kyb_badge.tone}">${escapeHtml(c.kyb_badge.label)}</span>`;

            tr.insertCell(4).innerHTML = `<span class="badge badge-light-${c.status_badge.tone}">${escapeHtml(c.status_badge.label)}</span>`;

            tr.insertCell(5).innerHTML = `
                <div class="fs-7">
                    <div><span class="text-muted">Total:</span> <span class="fw-semibold">${c.documents_count}</span></div>
                    ${c.pending_documents_count ? `<div><span class="text-warning">Pending: ${c.pending_documents_count}</span></div>` : ''}
                    ${c.approved_documents_count ? `<div><span class="text-success">Approved: ${c.approved_documents_count}</span></div>` : ''}
                </div>
            `;

            tr.insertCell(6).innerHTML = `<span class="badge badge-light-dark">${c.representatives_count}</span>`;

            tr.insertCell(7).innerHTML = `<span class="text-muted fs-7">${c.age_days}d</span>`;

            tr.insertCell(8).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openCompliance(${c.id}, '${escapeHtml(c.name).replace(/'/g, "\\'")}')" style="height:32px;">
                        <i class="ki-duotone ki-shield-tick fs-4"><span class="path1"></span><span class="path2"></span></i>
                        Review
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="openCompliance(${c.id}, '${escapeHtml(c.name).replace(/'/g, "\\'")}')" title="Compliance" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-setting-3 fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
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
            loadQueue();
        }
    };

    // The compliance modals + openCompliance + all its JS
    // need to be available here too. Extracted below into a partial.
</script>

{{-- Load the shared compliance modal + script --}}
@include('company.compliance._compliance_script')

@endpush