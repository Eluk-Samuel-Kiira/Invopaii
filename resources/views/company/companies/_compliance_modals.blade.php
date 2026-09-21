{{-- ═══════════════════════════════════════════════════════════
     COMPLIANCE MODALS
     Representatives · Documents · Verification Checks
     ═══════════════════════════════════════════════════════════ --}}

{{-- Compliance Modal --}}
<div class="modal fade" id="kt_modal_compliance" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-950px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Compliance</h2>
                    <div class="text-muted fs-7" id="compliance_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                {{-- Tabs --}}
                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#comp_tab_reps">
                            Representatives
                            <span class="badge badge-light-primary ms-1" id="comp_reps_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#comp_tab_docs">
                            Documents
                            <span class="badge badge-light-primary ms-1" id="comp_docs_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#comp_tab_checks">
                            Verification Checks
                            <span class="badge badge-light-primary ms-1" id="comp_checks_count">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- Representatives --}}
                    <div class="tab-pane fade show active" id="comp_tab_reps">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddRepresentative()">
                                <i class="ki-duotone ki-plus fs-3"></i> Add Representative
                            </button>
                        </div>

                        <div id="comp_reps_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="comp_reps_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No representatives on file.</p>
                        </div>
                        <div id="comp_reps_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Roles</th>
                                        <th>Ownership</th>
                                        <th>ID</th>
                                        <th>KYC</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="comp_reps_body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="tab-pane fade" id="comp_tab_docs">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <div class="text-muted fs-7">PDF, JPG, PNG — max 10 MB</div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openUploadDocument()">
                                <i class="ki-duotone ki-cloud-add fs-3"></i> Upload Document
                            </button>
                        </div>

                        <div id="comp_docs_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="comp_docs_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No documents uploaded.</p>
                        </div>
                        <div id="comp_docs_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Type</th>
                                        <th>File</th>
                                        <th>Representative</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="comp_docs_body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Verification Checks --}}
                    <div class="tab-pane fade" id="comp_tab_checks">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openRunCheck()">
                                <i class="ki-duotone ki-shield-search fs-3"></i> Run Check
                            </button>
                        </div>

                        <div id="comp_checks_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="comp_checks_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No verification checks run yet.</p>
                        </div>
                        <div id="comp_checks_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Type</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Completed</th>
                                        <th class="text-end">Info</th>
                                    </tr>
                                </thead>
                                <tbody id="comp_checks_body"></tbody>
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

{{-- Add/Edit Representative Modal --}}
<div class="modal fade" id="kt_modal_representative" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="rep_modal_title">Add Representative</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="representativeForm">
                    @csrf
                    <input type="hidden" id="rep_id">
                    <input type="hidden" id="rep_company_id">

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">First Name</label>
                            <input type="text" class="form-control form-control-solid" id="rep_first_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                            <input type="text" class="form-control form-control-solid" id="rep_last_name" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" id="rep_email" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="text" class="form-control form-control-solid" id="rep_phone" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Date of Birth</label>
                            <input type="date" class="form-control form-control-solid" id="rep_date_of_birth" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Nationality (ISO2)</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="rep_nationality" maxlength="2" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" id="rep_job_title" />
                        </div>
                    </div>

                    <hr class="my-7">
                    <h4 class="fw-bold mb-5">Roles</h4>

                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="rep_is_director" value="1" />
                            <span class="form-check-label fw-semibold">Director</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="rep_is_owner" value="1" />
                            <span class="form-check-label fw-semibold">UBO / Owner</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="rep_is_signatory" value="1" />
                            <span class="form-check-label fw-semibold">Signatory</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="rep_is_primary_contact" value="1" />
                            <span class="form-check-label fw-semibold">Primary Contact</span>
                        </label>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Ownership %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid" id="rep_ownership_percent" />
                    </div>

                    <hr class="my-7">
                    <h4 class="fw-bold mb-5">Identity Document</h4>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Type</label>
                            <select class="form-select form-select-solid" id="rep_id_document_type">
                                <option value="">Select…</option>
                                <option value="passport">Passport</option>
                                <option value="national_id">National ID</option>
                                <option value="drivers_license">Driver's License</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Number</label>
                            <input type="text" class="form-control form-control-solid" id="rep_id_document_number" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Expires</label>
                            <input type="date" class="form-control form-control-solid" id="rep_id_document_expires_on" />
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="repSaveBtn">
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

{{-- Upload Document Modal --}}
<div class="modal fade" id="kt_modal_upload_document" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Upload Document</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="documentUploadForm">
                    @csrf
                    <input type="hidden" id="doc_company_id">

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Document Type</label>
                        <select class="form-select form-select-solid" id="doc_type" required>
                            <option value="">Select…</option>
                            <option value="certificate_of_incorporation">Certificate of Incorporation</option>
                            <option value="tax_certificate">Tax Certificate</option>
                            <option value="bank_statement">Bank Statement</option>
                            <option value="utility_bill">Utility Bill</option>
                            <option value="id_front">ID Front</option>
                            <option value="id_back">ID Back</option>
                            <option value="selfie">Selfie</option>
                            <option value="memarts">Memorandum & Articles</option>
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Representative (optional)</label>
                        <select class="form-select form-select-solid" id="doc_representative_id">
                            <option value="">— None —</option>
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">File</label>
                        <input type="file" class="form-control form-control-solid" id="doc_file" accept=".jpg,.jpeg,.png,.pdf" required />
                        <div class="text-muted fs-7 mt-1">PDF, JPG, PNG — max 10 MB</div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Expires On (optional)</label>
                        <input type="date" class="form-control form-control-solid" id="doc_expires_on" />
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="docUploadBtn">
                            <span class="indicator-label">Upload</span>
                            <span class="indicator-progress">Uploading...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Run Check Modal --}}
<div class="modal fade" id="kt_modal_run_check" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Run Verification Check</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <input type="hidden" id="check_company_id">
                <div class="fv-row mb-7">
                    <label class="required fw-semibold fs-6 mb-2">Check Type</label>
                    <select class="form-select form-select-solid" id="check_type">
                        <option value="kyb_registry">KYB Registry Lookup</option>
                        <option value="aml_screening">AML Screening</option>
                        <option value="sanctions">Sanctions</option>
                        <option value="pep">PEP</option>
                        <option value="document_ocr">Document OCR</option>
                        <option value="liveness">Liveness</option>
                        <option value="bank_account">Bank Account Verification</option>
                    </select>
                </div>
                <div class="text-center pt-15">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="checkRunBtn">
                        <span class="indicator-label">Run Check</span>
                        <span class="indicator-progress">Queuing...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Document Preview Modal --}}
<div class="modal fade" id="kt_modal_doc_preview" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="doc_preview_title">Document</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body text-center p-0" style="min-height:70vh; background:#f5f5f5;">
                <iframe id="doc_preview_frame" src="" style="width:100%; height:70vh; border:0;"></iframe>
                <img id="doc_preview_image" src="" style="max-width:100%; max-height:70vh; display:none;" />
            </div>
            <div class="modal-footer">
                <a href="#" id="doc_preview_download" class="btn btn-primary" target="_blank">
                    <i class="ki-duotone ki-cloud-download fs-3"></i> Download
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>