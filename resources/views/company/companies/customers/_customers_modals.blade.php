<div class="modal fade" id="kt_modal_customers" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-950px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Customers</h2>
                    <div class="text-muted fs-7" id="customers_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                <div class="d-flex flex-wrap gap-2 mb-5 align-items-center">
                    <div class="position-relative flex-grow-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="custSearch" class="form-control form-control-solid ps-12" placeholder="Search name, email, phone, reference" />
                    </div>
                    <select id="custModeFilter" class="form-select form-select-solid w-125px">
                        <option value="">All modes</option>
                        <option value="test">Test</option>
                        <option value="live">Live</option>
                    </select>
                    <select id="custStatusFilter" class="form-select form-select-solid w-150px">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="blocked">Blocked</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openAddCustomer()">
                        <i class="ki-duotone ki-plus fs-3"></i> Add Customer
                    </button>
                </div>

                <div id="cust_loading" class="text-center py-10 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="cust_empty" class="text-center py-10 d-none">
                    <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <p class="text-muted">No customers found.</p>
                </div>
                <div id="cust_container" class="d-none">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase">
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Mode</th>
                                <th>LTV</th>
                                <th>Payments</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cust_body"></tbody>
                    </table>
                    <div id="cust_pagination" class="d-flex justify-content-between mt-5">
                        <div id="cust_info" class="text-muted"></div>
                        <nav><ul class="pagination m-0" id="cust_pager"></ul></nav>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Customer Modal --}}
<div class="modal fade" id="kt_modal_add_customer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="cust_modal_title">Add Customer</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="customerForm">
                    @csrf
                    <input type="hidden" id="cust_id">
                    <input type="hidden" id="cust_company_id">

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" class="form-control form-control-solid" id="cust_name" placeholder="Jane Smith" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="cust_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" id="cust_email" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="text" class="form-control form-control-solid" id="cust_phone" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Reference</label>
                            <input type="text" class="form-control form-control-solid" id="cust_reference" placeholder="Your internal ID" />
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Country</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="cust_country_code" maxlength="2" />
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="cust_preferred_currency" maxlength="3" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" id="cust_description" rows="2"></textarea>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="cust_tax_exempt" value="1" />
                                <span class="form-check-label fw-semibold">Tax exempt</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Tax ID</label>
                            <input type="text" class="form-control form-control-solid" id="cust_tax_id" />
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="custSaveBtn">
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

{{-- Customer Detail Modal --}}
<div class="modal fade" id="kt_modal_customer_detail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="cust_detail_name">Customer</h2>
                    <div class="text-muted fs-7" id="cust_detail_public_id">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="cust_detail_body">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>