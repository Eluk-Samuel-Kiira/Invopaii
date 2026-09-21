
<div class="modal fade" id="kt_modal_api_keys" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Developers</h2>
                    <div class="text-muted fs-7" id="api_keys_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#dev_tab_keys">
                            API Keys <span class="badge badge-light-primary ms-1" id="api_keys_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#dev_tab_logs">
                            Request Logs
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- API Keys --}}
                    <div class="tab-pane fade show active" id="dev_tab_keys">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddApiKey()">
                                <i class="ki-duotone ki-plus fs-3"></i> Generate Key
                            </button>
                        </div>

                        <div id="api_keys_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="api_keys_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No API keys yet.</p>
                        </div>
                        <div id="api_keys_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Name</th>
                                        <th>Key</th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                        <th>Last used</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="api_keys_body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Logs --}}
                    <div class="tab-pane fade" id="dev_tab_logs">
                        <div class="d-flex gap-2 mb-5">
                            <select id="logFilterMode" class="form-select form-select-solid w-125px">
                                <option value="">All modes</option>
                                <option value="live">Live</option>
                                <option value="test">Test</option>
                            </select>
                            <select id="logFilterStatus" class="form-select form-select-solid w-150px">
                                <option value="">All statuses</option>
                                <option value="200">2xx</option>
                                <option value="400">4xx</option>
                                <option value="500">5xx</option>
                            </select>
                            <button type="button" class="btn btn-light-primary" onclick="loadApiLogs(1)">
                                <i class="ki-duotone ki-arrows-circle fs-3"></i>
                            </button>
                        </div>

                        <div id="api_logs_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="api_logs_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No requests logged yet.</p>
                        </div>
                        <div id="api_logs_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Method</th>
                                        <th>Path</th>
                                        <th>Status</th>
                                        <th>Key</th>
                                        <th>Duration</th>
                                        <th>Time</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="api_logs_body"></tbody>
                            </table>
                            <div id="api_logs_pagination" class="d-flex justify-content-between mt-5">
                                <div id="api_logs_info" class="text-muted"></div>
                                <nav><ul class="pagination m-0" id="api_logs_pager"></ul></nav>
                            </div>
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

{{-- Generate API Key Modal --}}
<div class="modal fade" id="kt_modal_add_api_key" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Generate API Key</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="apiKeyForm">
                    @csrf
                    <input type="hidden" id="api_key_company_id">

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Name</label>
                        <input type="text" class="form-control form-control-solid" id="api_key_name" placeholder="Production server, Zapier..." />
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="api_key_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Type</label>
                            <select class="form-select form-select-solid" id="api_key_type" required>
                                <option value="secret">Secret (server-side)</option>
                                <option value="publishable">Publishable (client-side)</option>
                                <option value="restricted">Restricted (scoped)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Rate Limit (per minute)</label>
                            <input type="number" class="form-control form-control-solid" id="api_key_rate_limit" min="1" max="10000" placeholder="e.g. 600" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Expires At (optional)</label>
                            <input type="date" class="form-control form-control-solid" id="api_key_expires_at" />
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="apiKeySaveBtn">
                            <span class="indicator-label">Generate</span>
                            <span class="indicator-progress">Generating...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Plaintext Key Reveal Modal --}}
<div class="modal fade" id="kt_modal_reveal_api_key" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header bg-light-warning">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-key fs-2 text-warning me-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Copy your key now
                </h2>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="alert alert-warning d-flex align-items-center p-5 mb-7">
                    <i class="ki-duotone ki-information-5 fs-2tx text-warning me-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div>
                        <strong>This is the only time this key will be shown.</strong><br>
                        Store it securely. If you lose it, you'll need to rotate.
                    </div>
                </div>

                <label class="fw-semibold fs-6 mb-2">Your API Key</label>
                <div class="input-group input-group-solid mb-7">
                    <input type="text" class="form-control font-monospace" id="reveal_api_key_value" readonly />
                    <button type="button" class="btn btn-primary" onclick="copyApiKey()">
                        <i class="ki-duotone ki-copy fs-3"><span class="path1"></span><span class="path2"></span></i>
                        Copy
                    </button>
                </div>

                <div class="text-center pt-10">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        I've saved it — done
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- API Log Detail Modal --}}
<div class="modal fade" id="kt_modal_api_log_detail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Request Detail</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="api_log_detail_body">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>