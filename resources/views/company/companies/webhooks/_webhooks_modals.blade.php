<div class="modal fade" id="kt_modal_webhooks" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Webhooks</h2>
                    <div class="text-muted fs-7" id="webhooks_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">

                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#wh_tab_endpoints">
                            Endpoints <span class="badge badge-light-primary ms-1" id="wh_endpoints_count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#wh_tab_deliveries">
                            Deliveries
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- Endpoints --}}
                    <div class="tab-pane fade show active" id="wh_tab_endpoints">
                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openAddWebhook()">
                                <i class="ki-duotone ki-plus fs-3"></i> Add Endpoint
                            </button>
                        </div>

                        <div id="wh_endpoints_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="wh_endpoints_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No webhook endpoints configured.</p>
                        </div>
                        <div id="wh_endpoints_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>URL</th>
                                        <th>Mode</th>
                                        <th>Events</th>
                                        <th>Status</th>
                                        <th>Last success</th>
                                        <th>Failures</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="wh_endpoints_body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Deliveries --}}
                    <div class="tab-pane fade" id="wh_tab_deliveries">
                        <div class="d-flex gap-2 mb-5">
                            <select id="whFilterStatus" class="form-select form-select-solid w-150px">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="succeeded">Succeeded</option>
                                <option value="failed">Failed</option>
                                <option value="abandoned">Abandoned</option>
                            </select>
                            <button type="button" class="btn btn-light-primary" onclick="loadWebhookDeliveries(1)">
                                <i class="ki-duotone ki-arrows-circle fs-3"></i>
                            </button>
                        </div>

                        <div id="wh_deliveries_loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="wh_deliveries_empty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <p class="text-muted">No deliveries recorded yet.</p>
                        </div>
                        <div id="wh_deliveries_container" class="d-none">
                            <table class="table table-row-dashed align-middle fs-7">
                                <thead>
                                    <tr class="text-muted fw-bold text-uppercase">
                                        <th>Event</th>
                                        <th>Endpoint</th>
                                        <th>Status</th>
                                        <th>Attempt</th>
                                        <th>Code</th>
                                        <th>Duration</th>
                                        <th>Time</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="wh_deliveries_body"></tbody>
                            </table>
                            <div id="wh_deliveries_pagination" class="d-flex justify-content-between mt-5">
                                <div id="wh_deliveries_info" class="text-muted"></div>
                                <nav><ul class="pagination m-0" id="wh_deliveries_pager"></ul></nav>
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

{{-- Add/Edit Endpoint Modal --}}
<div class="modal fade" id="kt_modal_add_webhook" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="wh_modal_title">Add Endpoint</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="webhookForm">
                    @csrf
                    <input type="hidden" id="wh_id">
                    <input type="hidden" id="wh_company_id">

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">URL</label>
                            <input type="url" class="form-control form-control-solid font-monospace" id="wh_url" placeholder="https://example.com/webhooks/stardena" required />
                            <div class="text-muted fs-7 mt-1">Live endpoints must use HTTPS.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Mode</label>
                            <select class="form-select form-select-solid" id="wh_mode" required>
                                <option value="test">Test</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" id="wh_description" placeholder="Production server" />
                    </div>

                    <hr class="my-7">
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4 class="fw-bold m-0">Events to Send</h4>
                        <button type="button" class="btn btn-sm btn-light" onclick="toggleAllEvents()">
                            <span id="wh_toggle_all_label">Select all</span>
                        </button>
                    </div>

                    <div id="wh_event_grid" class="mb-7" style="max-height:350px; overflow-y:auto;">
                        <div class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>
                    </div>

                    <hr class="my-7">
                    <h4 class="fw-bold mb-5">Delivery Settings</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Timeout (seconds)</label>
                            <input type="number" class="form-control form-control-solid" id="wh_timeout" value="10" min="1" max="60" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Max Attempts</label>
                            <input type="number" class="form-control form-control-solid" id="wh_max_attempts" value="8" min="1" max="20" />
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="whSaveBtn">
                            <span class="indicator-label">Save Endpoint</span>
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

{{-- Secret Reveal Modal --}}
<div class="modal fade" id="kt_modal_reveal_webhook_secret" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header bg-light-warning">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-key fs-2 text-warning me-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Copy your signing secret
                </h2>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="alert alert-warning d-flex align-items-center p-5 mb-7">
                    <i class="ki-duotone ki-information-5 fs-2tx text-warning me-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div>
                        <strong>This is the only time this secret will be shown.</strong><br>
                        Use it to verify the <code>Stardena-Signature</code> header on incoming webhooks.
                    </div>
                </div>

                <label class="fw-semibold fs-6 mb-2">Your Signing Secret</label>
                <div class="input-group input-group-solid mb-7">
                    <input type="text" class="form-control font-monospace" id="reveal_webhook_secret_value" readonly />
                    <button type="button" class="btn btn-primary" onclick="copyWebhookSecret()">
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

{{-- Delivery Detail Modal --}}
<div class="modal fade" id="kt_modal_delivery_detail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-1100px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Delivery Detail</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="delivery_detail_body">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>