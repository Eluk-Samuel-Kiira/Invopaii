@extends('layouts.admin')

@section('title', 'Webhook Events')
@section('page_title', 'Webhook Events')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Payments</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Webhook Events</li>
@endsection

@section('content')
    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100">
                    <div class="position-relative w-100" style="max-width:280px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="whSearch" class="form-control form-control-solid ps-12" placeholder="Search event ID, reference, type" />
                    </div>
                    <select id="whStatusFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All statuses</option>
                        <option value="received">Received</option>
                        <option value="processed">Processed</option>
                        <option value="ignored">Ignored</option>
                        <option value="failed">Failed</option>
                        <option value="duplicate">Duplicate</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-200px">Event</th>
                                <th class="min-w-180px d-none d-md-table-cell">Provider</th>
                                <th class="min-w-150px">Type</th>
                                <th class="min-w-120px d-none d-md-table-cell">Signature</th>
                                <th class="min-w-120px">Status</th>
                                <th class="min-w-100px d-none d-lg-table-cell">Attempts</th>
                                <th class="min-w-150px d-none d-md-table-cell">Received</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="whTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No webhook events yet.</p>
            </div>

            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Event detail modal --}}
    <div class="modal fade" id="kt_modal_wh_detail" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold m-0" id="wh_modal_title">Webhook Event</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7" id="wh_modal_body">
                    <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    let currentPage = 1;
    let searchTimer = null;

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadEvents();

        document.getElementById('whSearch')?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { currentPage = 1; loadEvents(); }, 400);
        });

        document.getElementById('whStatusFilter')?.addEventListener('change', () => {
            currentPage = 1;
            loadEvents();
        });
    });

    function loadEvents() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        const params = new URLSearchParams({ page: currentPage, per_page: 25 });
        const s = document.getElementById('whSearch')?.value;
        const st = document.getElementById('whStatusFilter')?.value;
        if (s) params.set('search', s);
        if (st) params.set('status', st);

        fetch('{{ route("admin.webhook-events.data") }}?' + params.toString())
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (!data.data.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderEvents(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load events');
            });
    }

    function renderEvents(events) {
        const tbody = document.getElementById('whTableBody');
        tbody.innerHTML = '';

        events.forEach(e => {
            const row = tbody.insertRow();

            row.insertCell(0).innerHTML = `
                <div class="fw-bold font-monospace">${h(e.provider_event_id || e.uuid.substring(0, 16))}</div>
                ${e.provider_reference ? `<div class="text-muted fs-8 font-monospace">${h(e.provider_reference)}</div>` : ''}
            `;

            const prov = row.insertCell(1);
            prov.className = 'd-none d-md-table-cell';
            prov.innerHTML = e.provider ? `<span class="badge badge-light-info">${h(e.provider.name)}</span>` : '—';

            row.insertCell(2).innerHTML = `<span class="badge badge-light-dark font-monospace fs-8">${h(e.event_type || '—')}</span>`;

            const sig = row.insertCell(3);
            sig.className = 'd-none d-md-table-cell';
            sig.innerHTML = e.signature_valid === true
                ? '<span class="badge badge-light-success">Valid</span>'
                : e.signature_valid === false
                    ? '<span class="badge badge-light-danger">Invalid</span>'
                    : '<span class="badge badge-light-secondary">—</span>';

            row.insertCell(4).innerHTML = `<span class="badge badge-light-${e.status_badge.tone}">${h(e.status_badge.label)}</span>`;

            const att = row.insertCell(5);
            att.className = 'd-none d-lg-table-cell';
            att.innerHTML = `<span class="badge badge-light-dark">${e.process_attempts}</span>`;

            const rec = row.insertCell(6);
            rec.className = 'd-none d-md-table-cell text-muted';
            rec.textContent = e.created_at || '—';

            const act = row.insertCell(7);
            act.className = 'text-end';
            act.innerHTML = `
                <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewEvent(${e.id})" title="View" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            `;
        });
    }

    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} events`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); currentPage = page; loadEvents(); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Prev', false, !data.prev_page_url);
        addPage(data.current_page, data.current_page, true);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.viewEvent = function (id) {
        const body = document.getElementById('wh_modal_body');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('kt_modal_wh_detail')).show();

        fetch(`/admin/webhook-events/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger">${h(d.message)}</div>`;
                    return;
                }
                const e = d.data;

                const reprocessBtn = ['failed', 'received'].includes(e.status)
                    ? `<button class="btn btn-sm btn-light-warning" onclick="reprocessEvent(${e.id})">Reprocess</button>`
                    : '';

                body.innerHTML = `
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge badge-light-${e.status_badge.tone}">${h(e.status_badge.label)}</span>
                        ${e.provider ? `<span class="badge badge-light-info">${h(e.provider.name)}</span>` : ''}
                        ${e.signature_valid === true ? '<span class="badge badge-light-success">Signature Valid</span>' : ''}
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6"><strong>Event ID:</strong> <span class="font-monospace fs-8">${h(e.provider_event_id || '—')}</span></div>
                        <div class="col-md-6"><strong>Type:</strong> ${h(e.event_type || '—')}</div>
                        <div class="col-md-6"><strong>Reference:</strong> <span class="font-monospace fs-8">${h(e.provider_reference || '—')}</span></div>
                        <div class="col-md-6"><strong>Received:</strong> ${h(e.created_at)}</div>
                        <div class="col-md-6"><strong>Process attempts:</strong> ${e.process_attempts}</div>
                        <div class="col-md-6"><strong>Processed at:</strong> ${h(e.processed_at || '—')}</div>
                    </div>

                    ${e.processing_error ? `<div class="alert alert-danger mb-4">${h(e.processing_error)}</div>` : ''}

                    <h4 class="fw-bold mb-3">Payload</h4>
                    <pre class="bg-light p-3 rounded fs-8" style="max-height:300px; overflow:auto;">${h(JSON.stringify(e.payload, null, 2))}</pre>

                    <h4 class="fw-bold mt-5 mb-3">Headers</h4>
                    <pre class="bg-light p-3 rounded fs-8" style="max-height:200px; overflow:auto;">${h(JSON.stringify(e.headers, null, 2))}</pre>

                    ${reprocessBtn ? `<div class="d-flex justify-content-end mt-4">${reprocessBtn}</div>` : ''}
                `;
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger">Failed to load event</div>';
            });
    };

    window.reprocessEvent = function (id) {
        if (!confirm('Reprocess this event? It will run through the WebhookReceiver::process() pipeline again.')) return;

        fetch(`/admin/webhook-events/${id}/reprocess`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_wh_detail'))?.hide();
                loadEvents();
            } else window.showToast('error', d.message);
        });
    };

})();
</script>
@endpush