{{-- ═══════════════════════════════════════════════════════
     SUBSCRIPTIONS MODAL (per company)
     ═══════════════════════════════════════════════════════ --}}

<div class="modal fade" id="kt_modal_subscriptions" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Subscriptions</h2>
                    <div class="text-muted fs-7" id="sub_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                {{-- Stats strip --}}
                <div class="row g-3 mb-5">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Total</div>
                            <div class="fs-3 fw-bold" id="sub_stat_total">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Active</div>
                            <div class="fs-3 fw-bold text-success" id="sub_stat_active">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Trialing</div>
                            <div class="fs-3 fw-bold text-info" id="sub_stat_trialing">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Past Due</div>
                            <div class="fs-3 fw-bold text-danger" id="sub_stat_past_due">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Paused</div>
                            <div class="fs-3 fw-bold text-secondary" id="sub_stat_paused">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Cancelled</div>
                            <div class="fs-3 fw-bold text-secondary" id="sub_stat_cancelled">—</div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 mb-5">
                    <div class="position-relative flex-grow-1" style="max-width:280px; min-width:0;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="subSearch" class="form-control form-control-solid ps-12" placeholder="Search subscriptions" />
                    </div>
                    <select id="subModeFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
                    </select>
                    <select id="subStatusFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="trialing">Trialing</option>
                        <option value="past_due">Past Due</option>
                        <option value="paused">Paused</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="expired">Expired</option>
                    </select>
                    <div class="ms-md-auto">
                        <button type="button" class="btn btn-primary w-100 w-md-auto" onclick="openAddSubscription()">
                            <i class="ki-duotone ki-plus-square fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i> New Subscription
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div id="sub_loading" class="text-center py-10 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="sub_empty" class="text-center py-10 d-none">
                    <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <p class="text-muted">No subscriptions yet.</p>
                </div>
                <div id="sub_container" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-7">
                                    <th>Subscription</th>
                                    <th class="d-none d-md-table-cell">Customer</th>
                                    <th class="d-none d-md-table-cell">Interval</th>
                                    <th class="d-none d-lg-table-cell">Mode</th>
                                    <th>Next Billing</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sub_body"></tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Editor Modal --}}
<div class="modal fade" id="kt_modal_subscription_editor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="sub_editor_title">New Subscription</h2>
                    <div class="text-muted fs-7" id="sub_editor_subtitle">Not yet saved</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="subscriptionForm">
                    @csrf
                    <input type="hidden" id="sub_id">
                    <input type="hidden" id="sub_company_id">

                    <h4 class="fw-bold mb-4">Basics</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Customer</label>
                            <select class="form-select form-select-solid" id="sub_customer_id" required>
                                <option value="">Select customer…</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="sub_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="sub_currency" maxlength="3" required />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" id="sub_description" placeholder="e.g. Monthly hosting plan" />
                    </div>

                    <h4 class="fw-bold mb-4 mt-7">Billing Cycle</h4>

                    <div class="row mb-7">
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Every</label>
                            <input type="number" min="1" max="365" class="form-control form-control-solid" id="sub_billing_interval_count" value="1" required />
                        </div>
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Interval</label>
                            <select class="form-select form-select-solid" id="sub_billing_interval" required>
                                <option value="day">Day</option>
                                <option value="week">Week</option>
                                <option value="month" selected>Month</option>
                                <option value="year">Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Collection</label>
                            <select class="form-select form-select-solid" id="sub_collection_method" required>
                                <option value="charge_automatically">Charge automatically</option>
                                <option value="send_invoice">Send invoice</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Trial (days)</label>
                            <input type="number" min="0" max="365" class="form-control form-control-solid" id="sub_trial_period_days" placeholder="0" />
                        </div>
                    </div>

                    <h4 class="fw-bold mb-4 mt-7">Line Items</h4>

                    <div class="table-responsive mb-5">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th style="min-width:200px;">Name</th>
                                    <th style="width:90px;">Qty</th>
                                    <th style="width:150px;">Unit price</th>
                                    <th style="width:150px;">Tax</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="sub_lines_body"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-light mb-5" onclick="addBlankSubscriptionLine()">
                        <i class="ki-duotone ki-plus fs-4"></i> Add line
                    </button>

                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="subSaveBtn">
                            <span class="indicator-label">Save</span>
                            <span class="indicator-progress">Saving...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>