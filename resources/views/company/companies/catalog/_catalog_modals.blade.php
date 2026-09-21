{{-- Catalog Modal (4 tabs) --}}
<div class="modal fade" id="kt_modal_catalog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Catalog</h2>
                    <div class="text-muted fs-7" id="catalog_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#cat_tab_products">
                            Products <span class="badge badge-light-primary ms-1" id="cat_products_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#cat_tab_tax">
                            Tax Rates <span class="badge badge-light-primary ms-1" id="cat_tax_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#cat_tab_discounts">
                            Discounts <span class="badge badge-light-primary ms-1" id="cat_discounts_count">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Products --}}
                    <div class="tab-pane fade show active" id="cat_tab_products">
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-5">
                            <div class="position-relative flex-grow-1" style="max-width:320px;">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" id="catProductSearch" class="form-control form-control-solid ps-12" placeholder="Search products" />
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddProduct()">
                                <i class="ki-duotone ki-plus fs-3"></i> Add Product
                            </button>
                        </div>

                        <div id="cat_products_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="cat_products_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No products yet. Products are optional — you can also bill freeform.</p>
                        </div>
                        <div id="cat_products_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Product</th>
                                        <th class="d-none d-md-table-cell">SKU</th>
                                        <th class="d-none d-lg-table-cell">Mode</th>
                                        <th>Prices</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cat_products_body"></tbody>
                            </table>
                            <div id="cat_products_pagination" class="d-flex justify-content-between mt-5">
                                <div id="cat_products_info" class="text-muted"></div>
                                <nav><ul class="pagination m-0" id="cat_products_pager"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Tax Rates --}}
                    <div class="tab-pane fade" id="cat_tab_tax">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddTaxRate()">
                                <i class="ki-duotone ki-plus fs-3"></i> Add Tax Rate
                            </button>
                        </div>

                        <div id="cat_tax_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="cat_tax_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No tax rates configured.</p>
                        </div>
                        <div id="cat_tax_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Name</th>
                                        <th>Rate</th>
                                        <th class="d-none d-md-table-cell">Jurisdiction</th>
                                        <th class="d-none d-lg-table-cell">Mode</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cat_tax_body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Discounts --}}
                    <div class="tab-pane fade" id="cat_tab_discounts">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddDiscount()">
                                <i class="ki-duotone ki-plus fs-3"></i> Add Discount
                            </button>
                        </div>

                        <div id="cat_discounts_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="cat_discounts_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No discount codes created.</p>
                        </div>
                        <div id="cat_discounts_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Code</th>
                                        <th>Offer</th>
                                        <th class="d-none d-md-table-cell">Redemptions</th>
                                        <th class="d-none d-lg-table-cell">Expires</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cat_discounts_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Product Modal --}}
<div class="modal fade" id="kt_modal_add_product" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="prod_modal_title">Add Product</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="productForm">
                    @csrf
                    <input type="hidden" id="prod_id">
                    <input type="hidden" id="prod_company_id">

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" class="form-control form-control-solid" id="prod_name" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="prod_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" id="prod_description" rows="2"></textarea>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">SKU</label>
                            <input type="text" class="form-control form-control-solid" id="prod_sku" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Unit Label</label>
                            <input type="text" class="form-control form-control-solid" id="prod_unit_label" placeholder="seat, hour, month" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Tax Code</label>
                            <input type="text" class="form-control form-control-solid" id="prod_tax_code" />
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="prod_is_active" value="1" checked />
                            <span class="form-check-label fw-semibold">Active</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="prod_is_shippable" value="1" />
                            <span class="form-check-label fw-semibold">Shippable</span>
                        </label>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="prodSaveBtn">
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

{{-- Add/Edit Tax Rate Modal --}}
<div class="modal fade" id="kt_modal_add_tax_rate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="tax_modal_title">Add Tax Rate</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="taxRateForm">
                    @csrf
                    <input type="hidden" id="tax_id">
                    <input type="hidden" id="tax_company_id">

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Display Name</label>
                            <input type="text" class="form-control form-control-solid" id="tax_display_name" placeholder="VAT, Sales tax" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="tax_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Percentage</label>
                            <input type="number" step="0.001" min="0" max="100" class="form-control form-control-solid" id="tax_percentage" required />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Country (ISO2)</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="tax_country_code" maxlength="2" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Type</label>
                            <select class="form-select form-select-solid" id="tax_type">
                                <option value="">—</option>
                                <option value="vat">VAT</option>
                                <option value="gst">GST</option>
                                <option value="sales_tax">Sales Tax</option>
                                <option value="withholding">Withholding</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="tax_is_inclusive" value="1" />
                            <span class="form-check-label fw-semibold">Tax inclusive</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="tax_is_active" value="1" checked />
                            <span class="form-check-label fw-semibold">Active</span>
                        </label>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="taxSaveBtn">
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

{{-- Add/Edit Discount Modal --}}
<div class="modal fade" id="kt_modal_add_discount" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="disc_modal_title">Add Discount</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="discountForm">
                    @csrf
                    <input type="hidden" id="disc_id">
                    <input type="hidden" id="disc_company_id">

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="disc_code" placeholder="SAVE10" />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="disc_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Internal Name</label>
                        <input type="text" class="form-control form-control-solid" id="disc_name" />
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Type</label>
                            <select class="form-select form-select-solid" id="disc_type" required onchange="toggleDiscountTypeFields()">
                                <option value="percentage">Percentage</option>
                                <option value="fixed_amount">Fixed amount</option>
                            </select>
                        </div>
                        <div class="col-md-4 disc-percentage">
                            <label class="fw-semibold fs-6 mb-2">Percent Off</label>
                            <input type="number" step="0.001" min="0" max="100" class="form-control form-control-solid" id="disc_percent_off" />
                        </div>
                        <div class="col-md-4 disc-fixed d-none">
                            <label class="fw-semibold fs-6 mb-2">Amount Off (minor units)</label>
                            <input type="number" min="0" class="form-control form-control-solid" id="disc_amount_off" />
                        </div>
                        <div class="col-md-4 disc-fixed d-none">
                            <label class="fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="disc_currency" maxlength="3" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Duration</label>
                            <select class="form-select form-select-solid" id="disc_duration" required>
                                <option value="once">Once</option>
                                <option value="repeating">Repeating</option>
                                <option value="forever">Forever</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Max Redemptions</label>
                            <input type="number" min="1" class="form-control form-control-solid" id="disc_max_redemptions" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Starts At</label>
                            <input type="datetime-local" class="form-control form-control-solid" id="disc_starts_at" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Expires At</label>
                            <input type="datetime-local" class="form-control form-control-solid" id="disc_expires_at" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Minimum Order (minor units)</label>
                        <input type="number" min="0" class="form-control form-control-solid" id="disc_minimum_order_amount" />
                    </div>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="disc_is_active" value="1" checked />
                            <span class="form-check-label fw-semibold">Active</span>
                        </label>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="discSaveBtn">
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