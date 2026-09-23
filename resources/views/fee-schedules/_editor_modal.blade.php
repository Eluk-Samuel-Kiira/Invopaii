<div class="modal fade" id="kt_modal_fee_schedule" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0" id="fs_modal_title">New Fee Schedule</h2>
                    <div class="text-muted fs-7" id="fs_modal_subtitle">Define rates for a merchant tier</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="feeScheduleForm">
                    @csrf
                    <input type="hidden" id="fs_id">

                    {{-- Basics --}}
                    <h4 class="fw-bold mb-5">Basics</h4>

                    <div class="row g-3 mb-7">
                        <div class="col-md-5">
                            <label class="required fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" class="form-control form-control-solid" id="fs_name" placeholder="Standard Uganda" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="fs_code" placeholder="standard_ug" pattern="[a-z0-9_]+" required />
                            <div class="text-muted fs-7 mt-1">Lowercase, numbers, underscores only</div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex flex-wrap gap-5">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="fs_is_active" checked />
                                    <span class="form-check-label fw-semibold">Active</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="fs_is_default" />
                                    <span class="form-check-label fw-semibold">Default</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" id="fs_description" rows="2" placeholder="Optional internal notes"></textarea>
                    </div>

                    <div class="row g-3 mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Effective From</label>
                            <input type="date" class="form-control form-control-solid" id="fs_effective_from" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Effective To</label>
                            <input type="date" class="form-control form-control-solid" id="fs_effective_to" />
                            <div class="text-muted fs-7 mt-1">Leave blank for indefinite</div>
                        </div>
                    </div>

                    {{-- Rules --}}
                    <div class="d-flex justify-content-between align-items-center mt-9 mb-5">
                        <div>
                            <h4 class="fw-bold m-0">Rules</h4>
                            <div class="text-muted fs-7">Most specific rule wins. Lower priority number = higher precedence.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" onclick="addFeeRule()">
                            <i class="ki-duotone ki-plus fs-3"></i> Add Rule
                        </button>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-row-dashed align-middle fs-7 mb-0">
                            <thead class="bg-light">
                                <tr class="text-muted fw-bold text-uppercase fs-8">
                                    <th style="min-width:130px;">Fee Type</th>
                                    <th style="min-width:110px;">Method</th>
                                    <th style="width:90px;">Currency</th>
                                    <th style="width:90px;">%</th>
                                    <th style="width:100px;">Fixed</th>
                                    <th style="width:90px;">Min</th>
                                    <th style="width:90px;">Max</th>
                                    <th style="width:80px;">Tax %</th>
                                    <th style="width:80px;">Priority</th>
                                    <th style="width:80px;">Active</th>
                                    <th style="width:80px;"></th>
                                </tr>
                            </thead>
                            <tbody id="fs_rules_body">
                                {{-- Rules injected by JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div id="fs_rules_empty" class="text-center py-8 text-muted fs-7">
                        <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-2 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        No rules yet. Click <strong>Add Rule</strong> to define a rate.
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="fsSaveBtn">
                            <span class="indicator-label">Save Schedule</span>
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