@extends('layouts.admin')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Payments</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">All Payments</li>
@endsection

@section('content')

    {{-- Stats --}}
    <div class="row g-3 g-md-5 mb-5">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Total</div>
                <div class="fs-3 fs-md-2 fw-bold" id="stat_total">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Succeeded</div>
                <div class="fs-3 fs-md-2 fw-bold text-success" id="stat_succeeded">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Failed</div>
                <div class="fs-3 fs-md-2 fw-bold text-danger" id="stat_failed">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Pending</div>
                <div class="fs-3 fs-md-2 fw-bold text-info" id="stat_pending">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Refunded</div>
                <div class="fs-3 fs-md-2 fw-bold text-secondary" id="stat_refunded">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Volume</div>
                <div class="fs-5 fs-md-4 fw-bold text-primary" id="stat_volume">—</div>
            </div></div>
        </div>
    </div>

    {{-- Card --}}
    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100 flex-wrap">
                    <div class="position-relative w-100" style="max-width:260px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid ps-12" placeholder="Search payments" />
                    </div>
                    <select id="companyFilter" class="form-select form-select-solid" style="max-width:220px;">
                        <option value="">All companies</option>
                    </select>
                    <select id="statusFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All statuses</option>
                        <option value="pending">Pending (all)</option>
                        <option value="succeeded">Succeeded</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                        <option value="partially_refunded">Partially Refunded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="modeFilter" class="form-select form-select-solid" style="max-width:130px;">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
                    </select>
                    <select id="methodFilter" class="form-select form-select-solid" style="max-width:170px;">
                        <option value="">All methods</option>
                    </select>
                    <select id="providerFilter" class="form-select form-select-solid" style="max-width:200px;">
                        <option value="">All providers</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading payments...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-200px">Payment</th>
                                <th class="min-w-180px d-none d-md-table-cell">Company</th>
                                <th class="min-w-150px">Amount</th>
                                <th class="min-w-130px d-none d-lg-table-cell">Method</th>
                                <th class="min-w-150px d-none d-lg-table-cell">Provider</th>
                                <th class="min-w-120px">Status</th>
                                <th class="min-w-100px d-none d-xl-table-cell">Attempts</th>
                                <th class="min-w-130px d-none d-md-table-cell">Created</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No payments found.</p>
            </div>

            <div id="paginationContainer" class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-5 d-none">
                <div id="paginationInfo" class="text-muted fs-7 text-center text-sm-start"></div>
                <nav><ul class="pagination m-0 justify-content-center justify-content-sm-end" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Payment Detail Modal --}}
    <div class="modal fade" id="kt_modal_payment_detail" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="fw-bold m-0" id="pd_public_id">Payment</h2>
                        <div class="text-muted fs-7" id="pd_company">—</div>
                    </div>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7" id="pd_body">
                    <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@include('payments._script')
@endpush