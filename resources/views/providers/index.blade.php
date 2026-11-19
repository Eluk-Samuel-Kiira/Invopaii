@extends('layouts.admin')

@section('title', 'Payment Providers')
@section('page_title', 'Payment Providers')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Payments</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Providers</li>
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
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Mobile Money</div>
                <div class="fs-3 fs-md-2 fw-bold text-info" id="stat_mobile_money">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Aggregators</div>
                <div class="fs-3 fs-md-2 fw-bold text-warning" id="stat_aggregators">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Health Issues</div>
                <div class="fs-3 fs-md-2 fw-bold text-danger" id="stat_down">—</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card card-flush"><div class="card-body p-3 p-md-4">
                <div class="text-muted fs-8 fs-md-7 fw-bold text-uppercase mb-1">Routing Rules</div>
                <div class="fs-3 fs-md-2 fw-bold" id="stat_rules">—</div>
            </div></div>
        </div>
    </div>

    {{-- Card --}}
    <div class="card card-flush">
        <div class="card-header mt-6 flex-wrap gap-3">
            <div class="card-title w-100" style="min-width:0;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3 my-1 w-100">
                    <div class="position-relative w-100" style="max-width:280px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid ps-12" placeholder="Search providers" />
                    </div>
                    <select id="typeFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All types</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="acquirer">Acquirer</option>
                        <option value="aggregator">Aggregator</option>
                        <option value="wallet">Wallet</option>
                        <option value="bank">Bank</option>
                    </select>
                    <select id="statusFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    
                    <div class="card-toolbar m-0">
                        <button type="button" class="btn btn-light-primary w-100 w-md-auto me-2" data-bs-toggle="modal" data-bs-target="#kt_modal_routing_rules">
                            <i class="ki-duotone ki-route fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> Routing Rules
                        </button>
                        <button type="button" class="btn btn-primary w-100 w-md-auto" onclick="openAddProvider()">
                            <i class="ki-duotone ki-plus-square fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> Add Provider
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading providers...</p>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Provider</th>
                                <th class="min-w-120px">Type</th>
                                <th class="min-w-150px d-none d-md-table-cell">Supports</th>
                                <th class="min-w-100px d-none d-lg-table-cell">Health</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px d-none d-md-table-cell">Creds</th>
                                <th class="text-end min-w-150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="providersTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>

            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="text-muted">No providers found.</p>
            </div>
        </div>
    </div>

    {{-- Add/Edit Provider Modal --}}
    <div class="modal fade" id="kt_modal_add_provider" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="prov_modal_title">Add Provider</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="providerForm">
                        @csrf
                        <input type="hidden" id="prov_id">

                        <div class="row mb-7">
                            <div class="col-md-4">
                                <label class="required fw-semibold fs-6 mb-2">Code</label>
                                <input type="text" class="form-control form-control-solid" id="prov_code" placeholder="mtn_momo" required />
                                <div class="text-muted fs-7 mt-1">Lowercase, underscores only</div>
                            </div>
                            <div class="col-md-4">
                                <label class="required fw-semibold fs-6 mb-2">Name</label>
                                <input type="text" class="form-control form-control-solid" id="prov_name" placeholder="MTN Mobile Money" required />
                            </div>
                            <div class="col-md-4">
                                <label class="required fw-semibold fs-6 mb-2">Type</label>
                                <select class="form-select form-select-solid" id="prov_type" required>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="acquirer">Acquirer</option>
                                    <option value="aggregator">Aggregator</option>
                                    <option value="wallet">Wallet</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Supported Methods</label>
                                <select class="form-select form-select-solid" id="prov_methods" multiple size="5">
                                    <option value="card">Card</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="ussd">USSD</option>
                                    <option value="wallet">Wallet</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Supported Countries (ISO2)</label>
                                <input type="text" class="form-control form-control-solid" id="prov_countries" placeholder="UG,KE,TZ" />
                                <div class="text-muted fs-7 mt-1">Comma-separated. Blank = all.</div>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Supported Currencies</label>
                                <input type="text" class="form-control form-control-solid text-uppercase" id="prov_currencies" placeholder="UGX,KES,USD" />
                                <div class="text-muted fs-7 mt-1">Comma-separated. Blank = all.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="required fw-semibold fs-6 mb-2">Priority</label>
                                <input type="number" class="form-control form-control-solid" id="prov_priority" min="1" max="1000" value="100" required />
                                <div class="text-muted fs-7 mt-1">Lower = wins</div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-flex flex-wrap gap-4">
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="prov_is_active" checked />
                                        <span class="form-check-label fw-semibold">Active</span>
                                    </label>
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="prov_test_mode" checked />
                                        <span class="form-check-label fw-semibold">Test mode</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Webhook Path</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="prov_webhook_path" placeholder="/webhooks/mtn" />
                        </div>

                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="provSaveBtn">
                                <span class="indicator-label">Save</span>
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

    {{-- Credentials Modal --}}
    <div class="modal fade" id="kt_modal_credentials" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-1100px">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="fw-bold m-0">Credentials</h2>
                        <div class="text-muted fs-7" id="cred_provider_name">—</div>
                    </div>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <div class="d-flex justify-content-end mb-5">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openAddCredential()">
                            <i class="ki-duotone ki-plus fs-3"></i> Add Credentials
                        </button>
                    </div>

                    <div id="cred_loading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div id="cred_empty" class="text-center py-10 d-none">
                        <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <p class="text-muted">No credentials configured yet.</p>
                    </div>
                    <div id="cred_container" class="d-none">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th>Scope</th>
                                    <th>Mode</th>
                                    <th>Label</th>
                                    <th>Keys</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="cred_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Credential Modal --}}
    <div class="modal fade" id="kt_modal_add_credential" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="cred_modal_title">Add Credentials</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="credentialForm">
                        @csrf
                        <input type="hidden" id="cred_id">
                        <input type="hidden" id="cred_provider_id">

                        <div class="alert alert-info d-flex align-items-center p-4 mb-7">
                            <i class="ki-duotone ki-information-5 fs-2tx text-info me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="fs-7">
                                <strong>Scope:</strong> choose "Platform-wide" for credentials Stardena manages, or pick a company for merchant-owned accounts.
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Company (blank = platform-wide)</label>
                                <select class="form-select form-select-solid" id="cred_company_id">
                                    <option value="">Platform-wide</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Mode</label>
                                <select class="form-select form-select-solid" id="cred_mode" required>
                                    <option value="test">Test</option>
                                    <option value="live">Live</option>
                                </select>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Label</label>
                            <input type="text" class="form-control form-control-solid" id="cred_label" placeholder="Production account, Sandbox..." />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Public Key</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="cred_public_key" autocomplete="off" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Secret Key</label>
                            <input type="password" class="form-control form-control-solid font-monospace" id="cred_secret_key" autocomplete="new-password" />
                            <div class="text-muted fs-7 mt-1">Stored encrypted. Leave blank on edit to keep the current value.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Webhook Secret</label>
                            <input type="password" class="form-control form-control-solid font-monospace" id="cred_webhook_secret" autocomplete="new-password" />
                            <div class="text-muted fs-7 mt-1">Used to verify webhook signatures. Leave blank on edit to keep.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Merchant Account ID</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="cred_merchant_account_id" />
                        </div>

                        <div class="d-flex flex-wrap gap-5 mb-7">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="cred_is_active" checked />
                                <span class="form-check-label fw-semibold">Active</span>
                            </label>
                        </div>

                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="credSaveBtn">
                                <span class="indicator-label">Save</span>
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

    {{-- Routing Rules Modal --}}
    <div class="modal fade" id="kt_modal_routing_rules" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-1250px">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="fw-bold m-0">Routing Rules</h2>
                        <div class="text-muted fs-7">Decide which provider handles each payment</div>
                    </div>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <div class="d-flex justify-content-end mb-5">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openAddRule()">
                            <i class="ki-duotone ki-plus fs-3"></i> Add Rule
                        </button>
                    </div>

                    <div id="rule_loading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div id="rule_empty" class="text-center py-10 d-none">
                        <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <p class="text-muted">No routing rules configured. Payments will fall back to any active provider.</p>
                    </div>
                    <div id="rule_container" class="d-none">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th>Rule</th>
                                    <th>Matches</th>
                                    <th>Provider</th>
                                    <th>Fallback</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rule_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Rule Modal --}}
    <div class="modal fade" id="kt_modal_add_rule" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="rule_modal_title">Add Routing Rule</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="ruleForm">
                        @csrf
                        <input type="hidden" id="rule_id">

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Name (optional)</label>
                                <input type="text" class="form-control form-control-solid" id="rule_name" placeholder="MTN Uganda" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Company (blank = platform-wide)</label>
                                <select class="form-select form-select-solid" id="rule_company_id">
                                    <option value="">Platform-wide</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-4">
                                <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                                <select class="form-select form-select-solid" id="rule_payment_method">
                                    <option value="">Any</option>
                                    <option value="card">Card</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="ussd">USSD</option>
                                    <option value="wallet">Wallet</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-semibold fs-6 mb-2">Country</label>
                                <select class="form-select form-select-solid" id="rule_country_code">
                                    <option value="">Any</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-semibold fs-6 mb-2">Currency</label>
                                <select class="form-select form-select-solid" id="rule_currency">
                                    <option value="">Any</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Provider</label>
                                <select class="form-select form-select-solid" id="rule_payment_provider_id" required>
                                    <option value="">Select provider…</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Fallback Provider</label>
                                <select class="form-select form-select-solid" id="rule_fallback_provider_id">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-4">
                                <label class="required fw-semibold fs-6 mb-2">Priority</label>
                                <input type="number" class="form-control form-control-solid" id="rule_priority" min="1" max="1000" value="100" required />
                            </div>
                            <div class="col-md-4">
                                <label class="required fw-semibold fs-6 mb-2">Traffic %</label>
                                <input type="number" class="form-control form-control-solid" id="rule_traffic_percentage" min="0" max="100" value="100" required />
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="rule_is_active" checked />
                                    <span class="form-check-label fw-semibold">Active</span>
                                </label>
                            </div>
                        </div>

                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="ruleSaveBtn">
                                <span class="indicator-label">Save</span>
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
(function () {
    'use strict';

    let providersList = [];
    let credentialsList = [];
    let rulesList = [];
    let currentProviderId = null;
    let formOptions = { companies: [], countries: [], currencies: [], providers: [], payment_methods: [], types: [] };

    const ZERO_DECIMAL = ['UGX','RWF','BIF','XOF','XAF','JPY','KRW','VND','CLP','ISK','XPF'];

    function h(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    /* ══════ PROVIDERS LIST ══════ */

    function loadStats() {
        fetch('{{ route("admin.providers.stats") }}')
            .then(r => r.json())
            .then(s => {
                document.getElementById('stat_total').textContent = s.total ?? '—';
                document.getElementById('stat_active').textContent = s.active ?? '—';
                document.getElementById('stat_mobile_money').textContent = s.mobile_money ?? '—';
                document.getElementById('stat_aggregators').textContent = s.aggregators ?? '—';
                document.getElementById('stat_down').textContent = s.down ?? '—';
                document.getElementById('stat_rules').textContent = s.rules ?? '—';
            })
            .catch(() => {});
    }

    function loadProviders() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');

        const params = new URLSearchParams();
        const search = document.getElementById('searchInput')?.value;
        const type = document.getElementById('typeFilter')?.value;
        const status = document.getElementById('statusFilter')?.value;
        if (search) params.set('search', search);
        if (type) params.set('type', type);
        if (status) params.set('status', status);

        fetch(`{{ route("admin.providers.data") }}?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                providersList = data.data || [];

                if (!providersList.length) {
                    noData.classList.remove('d-none');
                    return;
                }
                table.classList.remove('d-none');
                renderProviders();
            })
            .catch(() => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load providers');
            });
    }

    function renderProviders() {
        const body = document.getElementById('providersTableBody');
        body.innerHTML = '';

        providersList.forEach(p => {
            const supports = [];
            if ((p.supported_methods || []).includes('card')) supports.push('Card');
            if ((p.supported_methods || []).includes('mobile_money')) supports.push('Mobile Money');
            if ((p.supported_methods || []).includes('bank_transfer')) supports.push('Bank Transfer');
            if ((p.supported_methods || []).includes('wallet')) supports.push('Wallet');

            const methodsHtml = supports.length
                ? supports.map(x => `<span class="badge badge-light-primary fs-8 me-1">${h(x)}</span>`).join('')
                : '<span class="text-muted">—</span>';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px me-3" style="background:#f1f1f4;">
                            <span class="text-gray-700 fw-bold">${h(p.code.substring(0, 2).toUpperCase())}</span>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-800">${h(p.name)}</div>
                            <div class="text-muted fs-8 font-monospace">${h(p.code)}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-light-dark">${h(p.type_label)}</span></td>
                <td class="d-none d-md-table-cell">${methodsHtml}</td>
                <td class="d-none d-lg-table-cell"><span class="badge badge-light-${p.health_badge.tone}">${h(p.health_badge.label)}</span></td>
                <td><span class="badge badge-light-${p.status_badge.tone}">${h(p.status_badge.label)}</span></td>
                <td class="d-none d-md-table-cell"><span class="badge badge-light-info">${p.credentials_count}</span></td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="manageCredentials(${p.id}, '${h(p.name).replace(/'/g, "\\'")}')" title="Credentials" style="width:28px;height:28px;"><i class="ki-duotone ki-key fs-4 text-warning"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editProvider(${p.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="toggleProvider(${p.id})" title="${p.is_active ? 'Disable' : 'Enable'}" style="width:28px;height:28px;"><i class="ki-duotone ki-${p.is_active ? 'minus-circle' : 'check'} fs-4 ${p.is_active ? 'text-warning' : 'text-success'}"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteProvider(${p.id}, '${h(p.name).replace(/'/g, "\\'")}')" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>
                    </div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    document.getElementById('searchInput')?.addEventListener('input', debounce(loadProviders));
    document.getElementById('typeFilter')?.addEventListener('change', loadProviders);
    document.getElementById('statusFilter')?.addEventListener('change', loadProviders);

    function debounce(fn, wait = 400) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, wait);
        };
    }

    /* ══════ PROVIDER EDITOR ══════ */

    window.openAddProvider = function () {
        document.getElementById('prov_modal_title').textContent = 'Add Provider';
        document.getElementById('prov_id').value = '';
        document.getElementById('providerForm').reset();
        document.getElementById('prov_is_active').checked = true;
        document.getElementById('prov_test_mode').checked = true;
        document.getElementById('prov_priority').value = 100;
        new bootstrap.Modal(document.getElementById('kt_modal_add_provider')).show();
    };

    window.editProvider = function (id) {
        fetch(`/admin/providers/${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return window.showToast('error', d.message);
                const p = d.data;

                document.getElementById('prov_modal_title').textContent = 'Edit ' + p.name;
                document.getElementById('prov_id').value = p.id;
                document.getElementById('prov_code').value = p.code || '';
                document.getElementById('prov_name').value = p.name || '';
                document.getElementById('prov_type').value = p.type || 'mobile_money';
                document.getElementById('prov_priority').value = p.priority || 100;
                document.getElementById('prov_countries').value = (p.supported_countries || []).join(',');
                document.getElementById('prov_currencies').value = (p.supported_currencies || []).join(',');
                document.getElementById('prov_webhook_path').value = p.webhook_path || '';
                document.getElementById('prov_is_active').checked = !!p.is_active;
                document.getElementById('prov_test_mode').checked = !!p.supports_test_mode;

                const sel = document.getElementById('prov_methods');
                Array.from(sel.options).forEach(opt => {
                    opt.selected = (p.supported_methods || []).includes(opt.value);
                });

                new bootstrap.Modal(document.getElementById('kt_modal_add_provider')).show();
            });
    };

    document.getElementById('providerForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('provSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('prov_id').value;

        const methods = Array.from(document.getElementById('prov_methods').selectedOptions).map(o => o.value);

        const countries = (document.getElementById('prov_countries').value || '')
            .split(',').map(s => s.trim().toUpperCase()).filter(Boolean);

        const currencies = (document.getElementById('prov_currencies').value || '')
            .split(',').map(s => s.trim().toUpperCase()).filter(Boolean);

        const payload = {
            code: document.getElementById('prov_code').value,
            name: document.getElementById('prov_name').value,
            type: document.getElementById('prov_type').value,
            priority: parseInt(document.getElementById('prov_priority').value),
            supported_methods: methods,
            supported_countries: countries.length ? countries : null,
            supported_currencies: currencies.length ? currencies : null,
            webhook_path: document.getElementById('prov_webhook_path').value || null,
            is_active: document.getElementById('prov_is_active').checked ? 1 : 0,
            supports_test_mode: document.getElementById('prov_test_mode').checked ? 1 : 0,
        };

        const url = id ? `/admin/providers/${id}` : '{{ route("admin.providers.store") }}';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_provider'))?.hide();
                loadProviders();
                loadStats();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.toggleProvider = function (id) {
        fetch(`/admin/providers/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadProviders();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

    window.deleteProvider = function (id, name) {
        if (!confirm(`Delete "${name}"? Routing rules using it must be removed first.`)) return;
        fetch(`/admin/providers/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadProviders();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

    /* ══════ CREDENTIALS ══════ */

    window.manageCredentials = function (providerId, providerName) {
        currentProviderId = providerId;
        document.getElementById('cred_provider_name').textContent = providerName;
        loadCredentials();
        new bootstrap.Modal(document.getElementById('kt_modal_credentials')).show();
    };

    function loadCredentials() {
        const loading = document.getElementById('cred_loading');
        const empty = document.getElementById('cred_empty');
        const container = document.getElementById('cred_container');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/providers/${currentProviderId}/credentials`)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                credentialsList = data.data || [];

                if (!credentialsList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderCredentials();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load credentials');
            });
    }

    function renderCredentials() {
        const body = document.getElementById('cred_body');
        body.innerHTML = '';

        credentialsList.forEach(c => {
            const scopeLabel = c.scope === 'platform'
                ? '<span class="badge badge-light-primary">Platform</span>'
                : `<span class="badge badge-light-info">${h(c.company?.name || 'Company')}</span>`;

            const keys = [];
            if (c.has_public_key) keys.push('public');
            if (c.has_secret_key) keys.push('secret');
            if (c.has_webhook_secret) keys.push('webhook');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${scopeLabel}</td>
                <td><span class="badge badge-light-${c.mode === 'live' ? 'danger' : 'info'}">${h(c.mode)}</span></td>
                <td>${h(c.label || '—')}</td>
                <td>${keys.length ? keys.map(k => `<span class="badge badge-light-dark fs-8 me-1">${h(k)}</span>`).join('') : '<span class="text-muted">—</span>'}</td>
                <td><span class="badge badge-light-${c.is_active ? 'success' : 'secondary'}">${c.is_active ? 'Active' : 'Off'}</span></td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editCredential(${c.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteCredential(${c.id})" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>
                    </div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    window.openAddCredential = function () {
        document.getElementById('cred_modal_title').textContent = 'Add Credentials';
        document.getElementById('cred_id').value = '';
        document.getElementById('credentialForm').reset();
        document.getElementById('cred_provider_id').value = currentProviderId;
        document.getElementById('cred_is_active').checked = true;
        document.getElementById('cred_mode').value = 'test';
        populateCompanySelect('cred_company_id');
        new bootstrap.Modal(document.getElementById('kt_modal_add_credential')).show();
    };

    window.editCredential = function (id) {
        const c = credentialsList.find(x => x.id === id);
        if (!c) return;

        document.getElementById('cred_modal_title').textContent = 'Edit Credentials';
        document.getElementById('cred_id').value = c.id;
        document.getElementById('cred_provider_id').value = c.payment_provider_id;
        populateCompanySelect('cred_company_id');
        document.getElementById('cred_company_id').value = c.company_id || '';
        document.getElementById('cred_mode').value = c.mode || 'test';
        document.getElementById('cred_label').value = c.label || '';
        document.getElementById('cred_public_key').value = '';
        document.getElementById('cred_secret_key').value = '';
        document.getElementById('cred_webhook_secret').value = '';
        document.getElementById('cred_merchant_account_id').value = c.merchant_account_id || '';
        document.getElementById('cred_is_active').checked = !!c.is_active;

        new bootstrap.Modal(document.getElementById('kt_modal_add_credential')).show();
    };

    document.getElementById('credentialForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('credSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('cred_id').value;
        const providerId = document.getElementById('cred_provider_id').value;

        const payload = {
            company_id: document.getElementById('cred_company_id').value || null,
            mode: document.getElementById('cred_mode').value,
            label: document.getElementById('cred_label').value || null,
            public_key: document.getElementById('cred_public_key').value || null,
            secret_key: document.getElementById('cred_secret_key').value || null,
            webhook_secret: document.getElementById('cred_webhook_secret').value || null,
            merchant_account_id: document.getElementById('cred_merchant_account_id').value || null,
            is_active: document.getElementById('cred_is_active').checked ? 1 : 0,
        };

        const url = id
            ? `/admin/providers/credentials/${id}`
            : `/admin/providers/${providerId}/credentials`;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_credential'))?.hide();
                loadCredentials();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.deleteCredential = function (id) {
        if (!confirm('Delete these credentials? Payments using them will fail.')) return;
        fetch(`/admin/providers/credentials/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadCredentials();
            } else window.showToast('error', d.message);
        });
    };

    function populateCompanySelect(selectId) {
        const sel = document.getElementById(selectId);
        if (!sel) return;
        sel.innerHTML = '<option value="">Platform-wide</option>' +
            formOptions.companies.map(c => `<option value="${c.value}">${h(c.label)}</option>`).join('');
    }

    /* ══════ ROUTING RULES ══════ */

    document.getElementById('kt_modal_routing_rules')?.addEventListener('show.bs.modal', loadRoutingRules);

    function loadRoutingRules() {
        const loading = document.getElementById('rule_loading');
        const empty = document.getElementById('rule_empty');
        const container = document.getElementById('rule_container');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch('{{ route("admin.routing-rules.index") }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('d-none');
                rulesList = data.data || [];

                if (!rulesList.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderRules();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load rules');
            });
    }

    function renderRules() {
        const body = document.getElementById('rule_body');
        body.innerHTML = '';

        rulesList.forEach(r => {
            const matches = [];
            if (r.payment_method) matches.push(r.payment_method.replace('_', ' '));
            if (r.country_code) matches.push(r.country_code);
            if (r.currency) matches.push(r.currency);
            if (!matches.length) matches.push('Any');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(r.name || 'Rule #' + r.id)}</div>
                    <div class="fs-8">${r.scope === 'platform' ? '<span class="badge badge-light-primary">Platform</span>' : `<span class="badge badge-light-info">${h(r.company?.name)}</span>`}</div>
                </td>
                <td>${matches.map(m => `<span class="badge badge-light-dark fs-8 me-1">${h(m)}</span>`).join('')}</td>
                <td>${r.provider ? `<span class="badge badge-light-success">${h(r.provider.name)}</span>` : '—'}</td>
                <td>${r.fallback_provider ? `<span class="badge badge-light-warning">${h(r.fallback_provider.name)}</span>` : '<span class="text-muted">—</span>'}</td>
                <td>${r.priority}</td>
                <td><span class="badge badge-light-${r.is_active ? 'success' : 'secondary'}">${r.is_active ? 'Active' : 'Off'}</span></td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editRule(${r.id})" title="Edit" style="width:28px;height:28px;"><i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="toggleRule(${r.id})" title="Toggle" style="width:28px;height:28px;"><i class="ki-duotone ki-switch fs-4 text-info"><span class="path1"></span><span class="path2"></span></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteRule(${r.id})" title="Delete" style="width:28px;height:28px;"><i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>
                    </div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    window.openAddRule = function () {
        document.getElementById('rule_modal_title').textContent = 'Add Routing Rule';
        document.getElementById('rule_id').value = '';
        document.getElementById('ruleForm').reset();
        document.getElementById('rule_priority').value = 100;
        document.getElementById('rule_traffic_percentage').value = 100;
        document.getElementById('rule_is_active').checked = true;
        populateRuleSelects();
        new bootstrap.Modal(document.getElementById('kt_modal_add_rule')).show();
    };

    window.editRule = function (id) {
        const r = rulesList.find(x => x.id === id);
        if (!r) return;

        document.getElementById('rule_modal_title').textContent = 'Edit Rule';
        document.getElementById('rule_id').value = r.id;
        populateRuleSelects();
        document.getElementById('rule_name').value = r.name || '';
        document.getElementById('rule_company_id').value = r.company_id || '';
        document.getElementById('rule_payment_method').value = r.payment_method || '';
        document.getElementById('rule_country_code').value = r.country_code || '';
        document.getElementById('rule_currency').value = r.currency || '';
        document.getElementById('rule_payment_provider_id').value = r.provider?.id || '';
        document.getElementById('rule_fallback_provider_id').value = r.fallback_provider?.id || '';
        document.getElementById('rule_priority').value = r.priority;
        document.getElementById('rule_traffic_percentage').value = r.traffic_percentage;
        document.getElementById('rule_is_active').checked = !!r.is_active;

        new bootstrap.Modal(document.getElementById('kt_modal_add_rule')).show();
    };

    function populateRuleSelects() {
        const company = document.getElementById('rule_company_id');
        company.innerHTML = '<option value="">Platform-wide</option>' +
            formOptions.companies.map(c => `<option value="${c.value}">${h(c.label)}</option>`).join('');

        const country = document.getElementById('rule_country_code');
        country.innerHTML = '<option value="">Any</option>' +
            formOptions.countries.map(c => `<option value="${c.value}">${h(c.label)}</option>`).join('');

        const currency = document.getElementById('rule_currency');
        currency.innerHTML = '<option value="">Any</option>' +
            formOptions.currencies.map(c => `<option value="${c.value}">${h(c.label)}</option>`).join('');

        const provider = document.getElementById('rule_payment_provider_id');
        provider.innerHTML = '<option value="">Select provider…</option>' +
            formOptions.providers.map(p => `<option value="${p.value}">${h(p.label)}</option>`).join('');

        const fallback = document.getElementById('rule_fallback_provider_id');
        fallback.innerHTML = '<option value="">None</option>' +
            formOptions.providers.map(p => `<option value="${p.value}">${h(p.label)}</option>`).join('');
    }

    document.getElementById('ruleForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('ruleSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('rule_id').value;

        const payload = {
            company_id: document.getElementById('rule_company_id').value || null,
            payment_provider_id: parseInt(document.getElementById('rule_payment_provider_id').value),
            fallback_provider_id: document.getElementById('rule_fallback_provider_id').value || null,
            name: document.getElementById('rule_name').value || null,
            payment_method: document.getElementById('rule_payment_method').value || null,
            country_code: document.getElementById('rule_country_code').value || null,
            currency: document.getElementById('rule_currency').value || null,
            priority: parseInt(document.getElementById('rule_priority').value),
            traffic_percentage: parseInt(document.getElementById('rule_traffic_percentage').value),
            is_active: document.getElementById('rule_is_active').checked ? 1 : 0,
        };

        const url = id
            ? `/admin/routing-rules/${id}`
            : '{{ route("admin.routing-rules.store") }}';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_rule'))?.hide();
                loadRoutingRules();
                loadStats();
            } else window.showToast('error', d.message || 'Save failed');
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.toggleRule = function (id) {
        fetch(`/admin/routing-rules/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadRoutingRules();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

    window.deleteRule = function (id) {
        if (!confirm('Delete this routing rule?')) return;
        fetch(`/admin/routing-rules/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window.showToast('success', d.message);
                loadRoutingRules();
                loadStats();
            } else window.showToast('error', d.message);
        });
    };

    /* ══════ INIT ══════ */

    document.addEventListener('DOMContentLoaded', function () {
        fetch('{{ route("admin.providers.form-options") }}')
            .then(r => r.json())
            .then(opts => {
                formOptions = opts;
                loadProviders();
                loadStats();
            })
            .catch(() => {
                loadProviders();
                loadStats();
            });
    });

})();
</script>
@endpush