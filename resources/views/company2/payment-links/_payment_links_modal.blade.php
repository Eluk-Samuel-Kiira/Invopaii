{{-- ═══════════════════════════════════════════════════════════
     PAYMENT LINKS MODAL
     Company-scoped list + editor
     ═══════════════════════════════════════════════════════════ --}}

{{-- List Modal --}}
<div class="modal fade" id="kt_modal_payment_links" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Payment Links</h2>
                    <div class="text-muted fs-7" id="pl_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                {{-- Stats strip --}}
                <div class="row g-3 mb-5" id="plStatsRow">
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Total</div>
                            <div class="fs-3 fw-bold" id="pl_stat_total">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Active</div>
                            <div class="fs-3 fw-bold text-success" id="pl_stat_active">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Expired</div>
                            <div class="fs-3 fw-bold text-warning" id="pl_stat_expired">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Completed</div>
                            <div class="fs-3 fw-bold text-info" id="pl_stat_completed">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Collected</div>
                            <div class="fs-5 fw-bold text-primary" id="pl_stat_collected">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border border-gray-300 border-dashed rounded p-3">
                            <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Payments</div>
                            <div class="fs-3 fw-bold" id="pl_stat_payments">—</div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 mb-5">
                    <div class="position-relative flex-grow-1" style="max-width:280px; min-width:0;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="plSearch" class="form-control form-control-solid ps-12" placeholder="Search links" />
                    </div>
                    <select id="plModeFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
                    </select>
                    <select id="plStatusFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                    <div class="ms-md-auto">
                        <button type="button" class="btn btn-primary w-100 w-md-auto" onclick="openAddPaymentLink()">
                            <i class="ki-duotone ki-plus-square fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i> New Link
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div id="pl_loading" class="text-center py-10 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="pl_empty" class="text-center py-10 d-none">
                    <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <p class="text-muted">No payment links yet.</p>
                </div>
                <div id="pl_container" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-7">
                                    <th>Link</th>
                                    <th class="d-none d-md-table-cell">Amount</th>
                                    <th class="d-none d-lg-table-cell">Mode</th>
                                    <th class="d-none d-md-table-cell">Usage</th>
                                    <th>Collected</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pl_body"></tbody>
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
<div class="modal fade" id="kt_modal_payment_link_editor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="pl_editor_title">New Payment Link</h2>
                    <div class="text-muted fs-7" id="pl_editor_subtitle">Not yet saved</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="paymentLinkForm">
                    @csrf
                    <input type="hidden" id="pl_id">
                    <input type="hidden" id="pl_company_id">

                    {{-- Basics --}}
                    <h4 class="fw-bold mb-4">Basics</h4>

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Title</label>
                            <input type="text" class="form-control form-control-solid" id="pl_title" placeholder="e.g. Consulting — March 2026" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="pl_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" id="pl_description" rows="2" placeholder="What is this payment for?"></textarea>
                    </div>

                    {{-- Amount --}}
                    <h4 class="fw-bold mb-4 mt-7">Amount</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount Type</label>
                            <select class="form-select form-select-solid" id="pl_amount_type" required onchange="togglePaymentLinkAmountFields()">
                                <option value="fixed">Fixed amount</option>
                                <option value="customer_chooses">Customer chooses</option>
                                <option value="line_items">Line items (skip — build later)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="pl_currency" maxlength="3" placeholder="UGX" required />
                        </div>
                    </div>

                    <div class="row mb-7" id="pl_fixed_amount_row">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount (major units)</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" id="pl_amount" placeholder="500000" />
                            <div class="text-muted fs-7 mt-1" id="pl_amount_hint">Enter the amount the customer will pay.</div>
                        </div>
                    </div>

                    <div class="row mb-7 d-none" id="pl_customer_chooses_rows">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Minimum Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" id="pl_minimum_amount" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Maximum Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" id="pl_maximum_amount" />
                        </div>
                    </div>

                    {{-- Collection --}}
                    <h4 class="fw-bold mb-4 mt-7">Collect from Customer</h4>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_collect_customer_name" checked />
                            <span class="form-check-label fw-semibold">Name</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_collect_email" checked />
                            <span class="form-check-label fw-semibold">Email</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_collect_phone" />
                            <span class="form-check-label fw-semibold">Phone</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_collect_billing_address" />
                            <span class="form-check-label fw-semibold">Billing address</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_collect_shipping_address" />
                            <span class="form-check-label fw-semibold">Shipping address</span>
                        </label>
                    </div>

                    {{-- Limits --}}
                    <h4 class="fw-bold mb-4 mt-7">Limits & Schedule</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Usage</label>
                            <select class="form-select form-select-solid" id="pl_usage_type" required onchange="togglePaymentLinkUsageFields()">
                                <option value="multi_use">Multi-use</option>
                                <option value="single_use">Single-use</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="pl_max_payments_row">
                            <label class="fw-semibold fs-6 mb-2">Max Payments (optional)</label>
                            <input type="number" min="1" class="form-control form-control-solid" id="pl_max_payments" placeholder="Leave blank for unlimited" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Expires At</label>
                            <input type="datetime-local" class="form-control form-control-solid" id="pl_expires_at" />
                            <div class="text-muted fs-7 mt-1">Leave blank to never expire.</div>
                        </div>
                    </div>

                    {{-- After completion --}}
                    <h4 class="fw-bold mb-4 mt-7">After Payment</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Behaviour</label>
                            <select class="form-select form-select-solid" id="pl_after_completion" onchange="togglePaymentLinkAfterCompletion()">
                                <option value="hosted_confirmation">Show confirmation page</option>
                                <option value="redirect">Redirect to URL</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="pl_success_url_row">
                            <label class="required fw-semibold fs-6 mb-2">Success URL</label>
                            <input type="url" class="form-control form-control-solid" id="pl_success_url" placeholder="https://..." />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Success Message</label>
                        <textarea class="form-control form-control-solid" id="pl_success_message" rows="2" placeholder="Thank you for your payment!"></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="pl_send_receipt" checked />
                            <span class="form-check-label fw-semibold">Email a receipt</span>
                        </label>
                    </div>

                    {{-- Meta --}}
                    <h4 class="fw-bold mb-4 mt-7">Meta</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Reference Prefix</label>
                            <input type="text" class="form-control form-control-solid" id="pl_reference_prefix" placeholder="OPTIONAL" maxlength="24" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Statement Descriptor</label>
                            <input type="text" class="form-control form-control-solid" id="pl_statement_descriptor" maxlength="22" placeholder="Appears on card statements" />
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="plSaveBtn">
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