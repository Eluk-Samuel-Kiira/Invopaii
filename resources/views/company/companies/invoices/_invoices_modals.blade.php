{{-- Invoices List Modal --}}
<div class="modal fade" id="kt_modal_invoices" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Invoices</h2>
                    <div class="text-muted fs-7" id="invoices_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="d-flex flex-column flex-sm-row gap-2 mb-5">
                    <div class="position-relative flex-grow-1" style="max-width:280px;">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y"><span class="path1"></span><span class="path2"></span></i>
                        <input type="text" id="invSearch" class="form-control form-control-solid ps-12" placeholder="Search number, customer" />
                    </div>
                    <select id="invModeFilter" class="form-select form-select-solid" style="max-width:150px;">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
                    </select>
                    <select id="invStatusFilter" class="form-select form-select-solid" style="max-width:180px;">
                        <option value="">All statuses</option>
                        <option value="draft">Draft</option>
                        <option value="open">Open</option>
                        <option value="paid">Paid</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="void">Void</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openAddInvoice()">
                        <i class="ki-duotone ki-plus fs-3"></i> New Invoice
                    </button>
                </div>

                <div id="inv_loading" class="text-center py-10 d-none"><div class="spinner-border text-primary"></div></div>
                <div id="inv_empty" class="text-center py-10 d-none">
                    <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <p class="text-muted">No invoices yet.</p>
                </div>
                <div id="inv_container" class="d-none">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase">
                                <th>Number</th>
                                <th class="d-none d-md-table-cell">Customer</th>
                                <th>Total</th>
                                <th class="d-none d-lg-table-cell">Due</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inv_body"></tbody>
                    </table>
                    <div id="inv_pagination" class="d-flex justify-content-between mt-5">
                        <div id="inv_info" class="text-muted"></div>
                        <nav><ul class="pagination m-0" id="inv_pager"></ul></nav>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Invoice Editor Modal (create / edit) --}}
<div class="modal fade" id="kt_modal_invoice_editor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="inv_editor_title">New Invoice</h2>
                    <div class="text-muted fs-7" id="inv_editor_subtitle">Draft — no number yet</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="invoiceForm">
                    @csrf
                    <input type="hidden" id="inv_id">
                    <input type="hidden" id="inv_company_id">

                    {{-- Header fields --}}
                    <div class="row g-3 mb-7">
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="inv_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="inv_currency" maxlength="3" required />
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Issue Date</label>
                            <input type="date" class="form-control form-control-solid" id="inv_issue_date" />
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Due Date</label>
                            <input type="date" class="form-control form-control-solid" id="inv_due_date" />
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div class="row g-3 mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Customer</label>
                            <select class="form-select form-select-solid" id="inv_customer_id">
                                <option value="">— Manual entry —</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" id="inv_customer_email" placeholder="customer@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Customer Name</label>
                            <input type="text" class="form-control form-control-solid" id="inv_customer_name" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="text" class="form-control form-control-solid" id="inv_customer_phone" />
                        </div>
                    </div>

                    {{-- Line items --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold m-0">Line Items</h4>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="openCatalogPicker()">
                                <i class="ki-duotone ki-package fs-4"><span class="path1"></span><span class="path2"></span></i> Pick from Catalog
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="addBlankInvoiceLine()">
                                <i class="ki-duotone ki-plus fs-4"></i> Add line
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive mb-5">
                        <table class="table table-row-dashed align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase">
                                    <th style="min-width:220px;">Description</th>
                                    <th style="width:100px;">Qty</th>
                                    <th style="width:140px;">Unit price</th>
                                    <th style="width:150px;">Tax</th>
                                    <th style="width:120px;" class="text-end">Line total</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="inv_lines_body"></tbody>
                        </table>
                    </div>

                    {{-- Totals + discount --}}
                    <div class="row g-3 mb-7">
                        <div class="col-md-7">
                            <label class="fw-semibold fs-6 mb-2">Discount Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-solid text-uppercase" id="inv_discount_code" placeholder="SAVE10" />
                                <button type="button" class="btn btn-light-primary" onclick="applyInvoiceDiscount()">Apply</button>
                                <button type="button" class="btn btn-light-danger" onclick="clearInvoiceDiscount()">Clear</button>
                            </div>
                            <div class="form-text" id="inv_discount_feedback"></div>
                            <input type="hidden" id="inv_discount_id" />
                        </div>
                        <div class="col-md-5">
                            <div class="border rounded p-4 bg-light">
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span class="fw-bold" id="inv_subtotal_display">—</span></div>
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount</span><span class="fw-bold text-danger" id="inv_discount_display">—</span></div>
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax</span><span class="fw-bold" id="inv_tax_display">—</span></div>
                                <div class="separator my-2"></div>
                                <div class="d-flex justify-content-between"><span class="fw-bold">Total</span><span class="fw-bold fs-4" id="inv_total_display">—</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="row g-3 mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Notes (visible to customer)</label>
                            <textarea class="form-control form-control-solid" id="inv_notes" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Terms</label>
                            <textarea class="form-control form-control-solid" id="inv_terms" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Internal notes</label>
                            <textarea class="form-control form-control-solid" id="inv_internal_notes" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-4 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="inv_allow_partial_payment" />
                            <span class="form-check-label fw-semibold">Allow partial payment</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="inv_auto_reminders_enabled" checked />
                            <span class="form-check-label fw-semibold">Send automatic reminders</span>
                        </label>
                    </div>

                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="invSaveBtn">
                            <span class="indicator-label">Save as Draft</span>
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

{{-- Invoice Detail Modal --}}
<div class="modal fade" id="kt_modal_invoice_detail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="inv_detail_number">Invoice</h2>
                    <div class="text-muted fs-7" id="inv_detail_customer">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="inv_detail_body">
                <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- Catalog Picker Modal --}}
<div class="modal fade" id="kt_modal_catalog_picker" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Pick from Catalog</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="catalog_picker_body">
                <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>