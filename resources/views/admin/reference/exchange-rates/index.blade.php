@extends('layouts.admin')

@section('title', 'Exchange Rates')
@section('page_title', 'Exchange Rates')

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
    <li class="breadcrumb-item text-muted">Exchange Rates</li>
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
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search pair / provider" />
                </div>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_rate">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i> Add Rate
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            {{-- Loading --}}
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading exchange rates...</p>
            </div>

            {{-- Table --}}
            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-100px">Pair</th>
                                <th class="min-w-150px">Rate</th>
                                <th class="min-w-100px">Markup</th>
                                <th class="min-w-150px">Effective Rate</th>
                                <th class="min-w-200px">Effective Window</th>
                                <th class="min-w-100px">Provider</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ratesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            {{-- No data --}}
            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No exchange rates found.</p>
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

    {{-- Add Rate Modal --}}
    <div class="modal fade" id="kt_modal_add_rate" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add Exchange Rate</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="addRateForm">
                        @csrf
                        @include('admin.reference.exchange-rates._form', ['prefix' => 'add'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addRateBtn">
                                <span class="indicator-label">Create Rate</span>
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

    {{-- Edit Rate Modal --}}
    <div class="modal fade" id="kt_modal_edit_rate" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Exchange Rate</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="editRateForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="rate_id" id="edit_rate_id">
                        @include('admin.reference.exchange-rates._form', ['prefix' => 'edit'])
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editRateBtn">
                                <span class="indicator-label">Update Rate</span>
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
    let currencyCodes = [];

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function statusBadge(r) {
        if (r.status === 'scheduled') return '<span class="badge badge-light-info">Scheduled</span>';
        if (r.status === 'expired') return '<span class="badge badge-light-secondary">Expired</span>';
        return '<span class="badge badge-light-success">Active</span>';
    }

    function loadCurrencyCodes() {
        fetch('{{ route("exchange-rates.currency-codes") }}')
            .then(res => res.json())
            .then(data => {
                currencyCodes = data;
                const options = data.map(c => `<option value="${c.code}">${escapeHtml(c.label)}</option>`).join('');
                ['add', 'edit'].forEach(p => {
                    const b = document.getElementById(`${p}_base_currency`);
                    const q = document.getElementById(`${p}_quote_currency`);
                    if (b) b.innerHTML = '<option value="">Select Base</option>' + options;
                    if (q) q.innerHTML = '<option value="">Select Quote</option>' + options;
                });
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadCurrencyCodes();
        loadRates();

        const searchInput = document.getElementById('searchInput');
        let timeout;
        searchInput?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadRates();
            }, 500);
        });

        // Live effective-rate preview
        ['add', 'edit'].forEach(p => {
            document.getElementById(`${p}_rate`)?.addEventListener('input', () => previewEffective(p));
            document.getElementById(`${p}_markup_percent`)?.addEventListener('input', () => previewEffective(p));
        });
    });

    function previewEffective(prefix) {
        const rate = parseFloat(document.getElementById(`${prefix}_rate`)?.value || 0);
        const markup = parseFloat(document.getElementById(`${prefix}_markup_percent`)?.value || 0);
        const el = document.getElementById(`${prefix}_effective_preview`);
        if (!el) return;

        if (!rate) {
            el.textContent = '—';
            return;
        }
        const effective = rate * (1 + markup / 100);
        el.textContent = effective.toFixed(12).replace(/\.?0+$/, '');
    }

    function loadRates() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        let url = `{{ route("exchange-rates.data") }}?page=${currentPage}&per_page=20`;
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
                window.showToast('error', 'Failed to load exchange rates');
                console.error(err);
            });
    }

    function renderTable(rates) {
        const tbody = document.getElementById('ratesTableBody');
        tbody.innerHTML = '';

        rates.forEach(r => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `<span class="fw-bold">${r.id}</span>`;

            row.insertCell(1).innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge badge-light-dark me-1">${escapeHtml(r.base_currency)}</span>
                    <i class="ki-duotone ki-arrow-right fs-7 mx-1"><span class="path1"></span><span class="path2"></span></i>
                    <span class="badge badge-light-dark">${escapeHtml(r.quote_currency)}</span>
                </div>
            `;

            row.insertCell(2).innerHTML = `<span class="font-monospace">${escapeHtml(r.rate)}</span>`;
            row.insertCell(3).innerHTML = `<span class="badge badge-light-warning">${parseFloat(r.markup_percent).toFixed(4)}%</span>`;
            row.insertCell(4).innerHTML = `<span class="font-monospace fw-bold text-primary">${escapeHtml(r.effective_rate)}</span>`;

            row.insertCell(5).innerHTML = `
                <div class="fs-7">
                    <div><span class="text-muted">From:</span> ${escapeHtml(r.effective_from_display ?? '—')}</div>
                    <div><span class="text-muted">To:</span> ${escapeHtml(r.effective_to_display ?? '— open —')}</div>
                </div>
            `;

            row.insertCell(6).innerHTML = r.provider
                ? `<span class="badge badge-light-primary">${escapeHtml(r.provider)}</span>`
                : '<span class="text-muted">—</span>';

            row.insertCell(7).innerHTML = statusBadge(r);

            row.insertCell(8).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    ${r.status === 'active' ? `
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="closeRate(${r.id})" title="Close rate" style="width:32px;height:32px;">
                            <i class="ki-duotone ki-lock fs-3 text-warning">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                    ` : ''}
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editRate(${r.id})" title="Edit" style="width:32px;height:32px;">
                        <i class="ki-duotone ki-setting-3 fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span>
                        </i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteRate(${r.id}, '${escapeHtml(r.pair)}')" title="Delete" style="width:32px;height:32px;">
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
            loadRates();
        }
    };

    window.closeRate = function (id) {
        if (!confirm('Close this rate? It will stop applying immediately.')) return;
        fetch(`/admin/exchange-rates/${id}/close`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadRates();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to close rate'));
    };

    window.editRate = function (id) {
        fetch(`/admin/exchange-rates/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                const r = data.data;

                document.getElementById('edit_rate_id').value = r.id;
                document.getElementById('edit_base_currency').value = r.base_currency ?? '';
                document.getElementById('edit_quote_currency').value = r.quote_currency ?? '';
                document.getElementById('edit_rate').value = r.rate ?? '';
                document.getElementById('edit_markup_percent').value = r.markup_percent ?? '0';
                document.getElementById('edit_provider').value = r.provider ?? '';

                // Format for datetime-local input: YYYY-MM-DDTHH:mm
                document.getElementById('edit_effective_from').value = r.effective_from
                    ? r.effective_from.substring(0, 16)
                    : '';
                document.getElementById('edit_effective_to').value = r.effective_to
                    ? r.effective_to.substring(0, 16)
                    : '';

                previewEffective('edit');

                new bootstrap.Modal(document.getElementById('kt_modal_edit_rate')).show();
            });
    };

    window.deleteRate = function (id, pair) {
        if (!confirm(`Delete rate "${pair}"? This cannot be undone.`)) return;
        fetch(`/admin/exchange-rates/${id}`, {
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
                loadRates();
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    document.getElementById('addRateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('addRateBtn');
        window.showButtonSpinner(btn);

        fetch('{{ route("exchange-rates.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_rate'))?.hide();
                this.reset();
                previewEffective('add');
                loadRates();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to create rate'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    document.getElementById('editRateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('editRateBtn');
        window.showButtonSpinner(btn);
        const id = document.getElementById('edit_rate_id').value;

        fetch(`/admin/exchange-rates/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_rate'))?.hide();
                loadRates();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to update rate'))
        .finally(() => window.hideButtonSpinner(btn));
    });
</script>
@endpush