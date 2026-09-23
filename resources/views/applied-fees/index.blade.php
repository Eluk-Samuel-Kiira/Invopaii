@extends('layouts.admin')

@section('title', 'Applied Fees')
@section('page_title', 'Applied Fees')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Billing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Applied Fees</li>
@endsection

@section('content')
    <div class="row g-3 g-md-5 mb-5">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Total Fees</div>
                <div class="fs-3 fs-md-2 fw-bold" id="stat_count">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Total Revenue</div>
                <div class="fs-3 fs-md-2 fw-bold text-primary" id="stat_total">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">This Month</div>
                <div class="fs-3 fs-md-2 fw-bold text-success" id="stat_month">—</div>
            </div></div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100">
                    <select id="companyFilter" class="form-select form-select-solid" style="max-width:240px;">
                        <option value="">All companies</option>
                    </select>
                    <select id="typeFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All fee types</option>
                    </select>
                    <select id="modeFilter" class="form-select form-select-solid" style="max-width:130px;">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
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
                                <th class="min-w-200px">Resource</th>
                                <th class="min-w-180px d-none d-md-table-cell">Company</th>
                                <th class="min-w-120px">Fee Type</th>
                                <th class="min-w-120px text-end">Base</th>
                                <th class="min-w-120px text-end">Fee</th>
                                <th class="min-w-150px d-none d-md-table-cell">Applied</th>
                                <th class="text-end min-w-80px"></th>
                            </tr>
                        </thead>
                        <tbody id="feesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No fees applied yet.</p>
            </div>

            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted fs-7"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Fee Detail Modal --}}
    <div class="modal fade" id="kt_modal_fee_detail" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold m-0">Fee Breakdown</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7" id="fee_detail_body">
                    <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@include('applied-fees._script')
@endpush