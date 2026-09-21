{{-- ═══════════════════════════════════════════════════════════
     BANK ACCOUNT MODALS
     ═══════════════════════════════════════════════════════════ --}}

{{-- Bank Accounts List Modal --}}
<div class="modal fade" id="kt_modal_bank_accounts" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold m-0">Bank Accounts</h2>
                    <div class="text-muted fs-7" id="bank_accounts_company_name">—</div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="d-flex justify-content-end mb-5">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openAddBankAccount()">
                        <i class="ki-duotone ki-plus fs-3"></i> Add Bank Account
                    </button>
                </div>

                <div id="comp_banks_loading" class="text-center py-10 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="comp_banks_empty" class="text-center py-10 d-none">
                    <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <p class="text-muted">No bank accounts on file.</p>
                </div>
                <div id="comp_banks_container" class="d-none">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase">
                                <th>Type</th>
                                <th>Account</th>
                                <th>Holder</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="comp_banks_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Bank Account Modal --}}
<div class="modal fade" id="kt_modal_bank_account" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="bank_modal_title">Add Bank Account</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="bankAccountForm">
                    @csrf
                    <input type="hidden" id="bank_id">
                    <input type="hidden" id="bank_company_id">

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Type</label>
                            <select class="form-select form-select-solid" id="bank_type" required>
                                <option value="bank_account">Bank Account</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="wallet">Wallet</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country (ISO2)</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="bank_country_code" maxlength="2" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="bank_currency" maxlength="3" required />
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="bank_is_default" value="1" />
                                <span class="form-check-label fw-semibold">Set as default for this currency</span>
                            </label>
                        </div>
                    </div>

                    <hr class="my-7">
                    <h4 class="fw-bold mb-5">Holder</h4>

                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Account Holder Name</label>
                            <input type="text" class="form-control form-control-solid" id="bank_account_holder_name" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Holder Type</label>
                            <select class="form-select form-select-solid" id="bank_account_holder_type" required>
                                <option value="company">Company</option>
                                <option value="individual">Individual</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-7">
                    <h4 class="fw-bold mb-5">Bank Details</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Bank Name</label>
                            <input type="text" class="form-control form-control-solid" id="bank_bank_name" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Bank Code</label>
                            <input type="text" class="form-control form-control-solid" id="bank_bank_code" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Account Number</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="bank_account_number" placeholder="Leave blank to keep current" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">IBAN</label>
                            <input type="text" class="form-control form-control-solid font-monospace" id="bank_iban" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">SWIFT / BIC</label>
                            <input type="text" class="form-control form-control-solid text-uppercase" id="bank_swift_bic" maxlength="16" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Routing Number</label>
                            <input type="text" class="form-control form-control-solid" id="bank_routing_number" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Sort Code</label>
                            <input type="text" class="form-control form-control-solid" id="bank_sort_code" />
                        </div>
                    </div>

                    <div id="bank_mobile_section" class="d-none">
                        <hr class="my-7">
                        <h4 class="fw-bold mb-5">Mobile Money Details</h4>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Network</label>
                                <select class="form-select form-select-solid" id="bank_mobile_network">
                                    <option value="">Select…</option>
                                    <option value="mtn">MTN</option>
                                    <option value="airtel">Airtel</option>
                                    <option value="mpesa">M-PESA</option>
                                    <option value="orange">Orange Money</option>
                                    <option value="vodafone">Vodafone Cash</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">MSISDN</label>
                                <input type="text" class="form-control form-control-solid font-monospace" id="bank_msisdn" placeholder="+256..." />
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="bankSaveBtn">
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