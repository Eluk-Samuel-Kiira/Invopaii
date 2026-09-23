@extends('layouts.admin')

@section('title', 'Fee Schedules')
@section('page_title', 'Fee Schedules')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Platform</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Fee Schedules</li>
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
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Active</div>
                <div class="fs-3 fs-md-2 fw-bold text-success" id="stat_active">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Default</div>
                <div class="fs-3 fs-md-2 fw-bold text-primary" id="stat_default">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Active Rules</div>
                <div class="fs-3 fs-md-2 fw-bold text-info" id="stat_rules">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Assigned</div>
                <div class="fs-3 fs-md-2 fw-bold text-warning" id="stat_assigned">—</div>
            </div></div>
        </div>
    </div>

    {{-- Card --}}
    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100">
                    <div class="position-relative w-100" style="max-width:260px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid ps-12" placeholder="Search schedules" />
                    </div>
                    <select id="statusFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select id="defaultFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All schedules</option>
                        <option value="1">Default only</option>
                    </select>
                </div>
            </div>
            <div class="card-toolbar m-0">
                <button type="button" class="btn btn-primary w-100 w-md-auto" onclick="openAddSchedule()">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i> New Schedule
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading fee schedules...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Schedule</th>
                                <th class="min-w-120px d-none d-md-table-cell">Code</th>
                                <th class="min-w-100px">Rules</th>
                                <th class="min-w-120px d-none d-lg-table-cell">Companies</th>
                                <th class="min-w-150px d-none d-md-table-cell">Effective</th>
                                <th class="min-w-120px">Status</th>
                                <th class="text-end min-w-180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="schedulesTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No fee schedules yet. Create one to start configuring rates.</p>
            </div>

            <div id="paginationContainer" class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-5 d-none">
                <div id="paginationInfo" class="text-muted fs-7 text-center text-sm-start"></div>
                <nav><ul class="pagination m-0 justify-content-center justify-content-sm-end" id="pagination"></ul></nav>
            </div>
        </div>
    </div>

    {{-- Editor Modal --}}
    @include('fee-schedules._editor_modal')

@endsection

@push('scripts')
@include('fee-schedules._script')
@endpush