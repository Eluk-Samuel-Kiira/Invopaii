@extends('layouts.admin')

@section('title', 'Users List')
@section('page_title', 'Users List')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">User Management</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Users</li>
@endsection

@section('content')
    <!--begin::Card-->
    <div class="card card-flush">
        <!--begin::Card header-->
        <div class="card-header mt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1 me-5">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Users" />
                </div>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i> Add User
                </button>
            </div>
        </div>
        
        <div class="card-body pt-0">
            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading users...</p>
            </div>
            
            <!-- Table Container -->
            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-50px">ID</th>
                                <th class="min-w-150px">User</th>
                                <th class="min-w-150px">Contact Info</th>
                                <th class="min-w-100px">Role</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px">Last Login</th>
                                <th class="min-w-100px">Created</th>
                                <th class="text-end min-w-150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody" class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
            
            <!-- No Data Message -->
            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <p class="text-muted">No users found.</p>
            </div>
            
            <!-- Pagination -->
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav>
                    <ul class="pagination m-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="kt_modal_add_user" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add User</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="addUserForm">
                        @csrf
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">First Name</label>
                                <input type="text" class="form-control form-control-solid" name="first_name" required />
                            </div>
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                                <input type="text" class="form-control form-control-solid" name="last_name" required />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" name="email" required />
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-4">
                                <label class="fw-semibold fs-6 mb-2">Country Code</label>
                                <select class="form-select form-select-solid" name="country_code">
                                    <option value="+1">+1 (USA)</option>
                                    <option value="+44">+44 (UK)</option>
                                    <option value="+256" selected>+256 (Uganda)</option>
                                    <option value="+254">+254 (Kenya)</option>
                                    <option value="+234">+234 (Nigeria)</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="fw-semibold fs-6 mb-2">Phone</label>
                                <input type="tel" class="form-control form-control-solid" name="phone" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Password</label>
                                <input type="password" class="form-control form-control-solid" name="password" required />
                            </div>
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                                <input type="password" class="form-control form-control-solid" name="password_confirmation" required />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Role</label>
                            <select class="form-select form-select-solid" name="role" id="add_role_select" required>
                                <option value="">Select Role</option>
                            </select>
                        </div>
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addUserBtn">
                                <span class="indicator-label">Create User</span>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="kt_modal_edit_user" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit User</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="editUserForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">First Name</label>
                                <input type="text" class="form-control form-control-solid" name="first_name" id="edit_first_name" required />
                            </div>
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                                <input type="text" class="form-control form-control-solid" name="last_name" id="edit_last_name" required />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" name="email" id="edit_email" required />
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-4">
                                <label class="fw-semibold fs-6 mb-2">Country Code</label>
                                <select class="form-select form-select-solid" name="country_code" id="edit_country_code">
                                    <option value="+1">+1 (USA)</option>
                                    <option value="+44">+44 (UK)</option>
                                    <option value="+256">+256 (Uganda)</option>
                                    <option value="+254">+254 (Kenya)</option>
                                    <option value="+234">+234 (Nigeria)</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="fw-semibold fs-6 mb-2">Phone</label>
                                <input type="tel" class="form-control form-control-solid" name="phone" id="edit_phone" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">New Password</label>
                                <input type="password" class="form-control form-control-solid" name="password" placeholder="Leave blank to keep current" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Confirm Password</label>
                                <input type="password" class="form-control form-control-solid" name="password_confirmation" />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Role</label>
                            <select class="form-select form-select-solid" name="role" id="edit_role_select" required>
                                <option value="">Select Role</option>
                            </select>
                        </div>
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editUserBtn">
                                <span class="indicator-label">Update User</span>
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

    {{-- View User Modal --}}
    <div class="modal fade" id="kt_modal_view_user" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">User Details</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">

                    {{-- Header --}}
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-80px symbol-circle me-5">
                            <img id="view_user_avatar" src="" alt="" style="object-fit:cover;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 class="fw-bold m-0" id="view_user_name">—</h3>
                                <span id="view_user_platform_badge" class="badge badge-light-danger d-none">Platform Staff</span>
                            </div>
                            <div class="text-muted" id="view_user_email">—</div>
                            <div class="text-muted fs-7" id="view_user_uuid">—</div>
                        </div>
                        <div class="text-end">
                            <div class="mb-2" id="view_user_status"></div>
                            <div id="view_user_2fa_badge"></div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#view_tab_overview">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#view_tab_security">Security</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#view_tab_rbac">Roles & Permissions</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#view_tab_devices">Devices</a></li>
                    </ul>

                    <div class="tab-content">
                        {{-- Overview --}}
                        <div class="tab-pane fade show active" id="view_tab_overview">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Contact</div>
                                        <div class="mb-2"><strong>Email:</strong> <span id="view_user_email_2">—</span></div>
                                        <div class="mb-2"><strong>Phone:</strong> <span id="view_user_phone">—</span></div>
                                        <div><strong>Country Code:</strong> <span id="view_user_cc">—</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Activity</div>
                                        <div class="mb-2"><strong>Created:</strong> <span id="view_user_created">—</span></div>
                                        <div class="mb-2"><strong>Last Login:</strong> <span id="view_user_last_login">—</span></div>
                                        <div><strong>Last IP:</strong> <span id="view_user_last_ip">—</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Context</div>
                                        <div class="mb-2"><strong>Company:</strong> <span id="view_user_company">—</span></div>
                                        <div class="mb-2"><strong>Mode:</strong> <span id="view_user_mode">—</span></div>
                                        <div><strong>Locale / TZ:</strong> <span id="view_user_locale">—</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Terms</div>
                                        <div><strong>Accepted:</strong> <span id="view_user_terms">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Security --}}
                        <div class="tab-pane fade" id="view_tab_security">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Two-Factor</div>
                                        <div class="mb-2"><strong>Enabled:</strong> <span id="view_user_2fa_tab">—</span></div>
                                        <div class="mb-2"><strong>Method:</strong> <span id="view_user_2fa_method">—</span></div>
                                        <div><strong>Confirmed:</strong> <span id="view_user_2fa_confirmed">—</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Login Security</div>
                                        <div class="mb-2"><strong>Failed attempts:</strong> <span id="view_user_failed_attempts">—</span></div>
                                        <div class="mb-2"><strong>Locked:</strong> <span id="view_user_locked">—</span></div>
                                        <div><strong>Password changed:</strong> <span id="view_user_pw_changed">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-5">
                                <button type="button" class="btn btn-light-danger btn-sm" id="view_user_unlock_btn">
                                    <i class="ki-duotone ki-lock-2 fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                    Unlock account
                                </button>
                                <button type="button" class="btn btn-light-warning btn-sm" id="view_user_toggle_platform_btn">
                                    <i class="ki-duotone ki-shield fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                    Toggle platform staff
                                </button>
                            </div>
                        </div>

                        {{-- RBAC --}}
                        <div class="tab-pane fade" id="view_tab_rbac">
                            <div class="mb-5">
                                <h4 class="fw-bold mb-3">Roles</h4>
                                <div id="view_user_roles" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="mb-5">
                                <h4 class="fw-bold mb-3">Role-inherited Permissions</h4>
                                <div id="view_user_role_perms" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-3">Direct Permissions</h4>
                                <div id="view_user_direct_perms" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>

                        {{-- Devices --}}
                        <div class="tab-pane fade" id="view_tab_devices">
                            <div id="view_user_devices_empty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                                <p class="text-muted">No devices recorded.</p>
                            </div>
                            <div class="table-responsive" id="view_user_devices_container">
                                <table class="table table-row-dashed align-middle fs-7">
                                    <thead>
                                        <tr class="text-muted fw-bold text-uppercase">
                                            <th>Device</th>
                                            <th>Platform / Browser</th>
                                            <th>IP</th>
                                            <th>Last active</th>
                                            <th>Trusted</th>
                                        </tr>
                                    </thead>
                                    <tbody id="view_user_devices_body"></tbody>
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

    <!-- Permissions Modal -->
    <div class="modal fade" id="kt_modal_user_permissions" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">User Permissions</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <div class="alert alert-info d-flex align-items-center p-5 mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div>
                            <strong id="permUserName"></strong><br>
                            <span class="text-muted" id="permUserRole"></span>
                        </div>
                    </div>
                    
                    <div class="mb-7">
                        <h4 class="fw-bold mb-3">Role Permissions (Inherited)</h4>
                        <div id="rolePermissionsList" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-7">
                        <h4 class="fw-bold mb-3">Direct Permissions</h4>
                        <div id="directPermissionsList" class="d-flex flex-wrap gap-2 mb-3"></div>
                        <div class="mt-4">
                            <label class="fw-semibold fs-6 mb-2">Assign New Permission</label>
                            <div class="d-flex gap-2">
                                <select id="assignPermissionSelect" class="form-select form-select-solid flex-grow-1">
                                    <option value="">Select Permission</option>
                                </select>
                                <button class="btn btn-primary" id="assignPermissionBtn">
                                    <span class="indicator-label">Assign</span>
                                    <span class="indicator-progress">... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
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
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let currentSearch = '';
    let allRoles = [];
    let currentUserId = null;
    let allPermissionsList = [];
    
    function formatRoleName(name) {
        if (!name) return '';
        return name.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    }
    
    function loadRoles() {
        fetch('{{ route("users.roles") }}')
            .then(res => res.json())
            .then(data => {
                allRoles = data;
                const options = data.map(r => `<option value="${r.name}">${formatRoleName(r.name)}</option>`).join('');
                document.getElementById('add_role_select').innerHTML = '<option value="">Select Role</option>' + options;
                document.getElementById('edit_role_select').innerHTML = '<option value="">Select Role</option>' + options;
            })
            .catch(err => console.error('Error loading roles:', err));
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        loadRoles();
        loadUsers();
        
        const searchInput = document.getElementById('searchInput');
        let timeout;
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    currentSearch = this.value;
                    currentPage = 1;
                    loadUsers();
                    const url = new URL(window.location.href);
                    if (currentSearch) {
                        url.searchParams.set('search', currentSearch);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.history.pushState({}, '', url);
                }, 500);
            });
        }
    });
    
    function loadUsers() {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('tableContainer');
        const noData = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');
        
        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');
        
        let url = `{{ route("users.data") }}?page=${currentPage}&per_page=20`;
        if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (data.data.length === 0) {
                    noData.classList.remove('d-none');
                } else {
                    table.classList.remove('d-none');
                    renderUsersTable(data.data);
                    renderPagination(data);
                    pagination.classList.remove('d-none');
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                window.showToast('error', 'Failed to load users');
                console.error(err);
            });
    }
    
    function renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '';
        
        users.forEach(user => {
            const row = tbody.insertRow();
            row.insertCell(0).innerHTML = `<span class="fw-bold">${user.id}</span>`;
            row.insertCell(1).innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px symbol-circle me-3">
                        <img src="${user.avatar || '{{ asset('assets/media/avatars/blank.png') }}'}" alt="${user.name}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    </div>
                    <div>
                        <div class="fw-bold text-gray-800">${escapeHtml(user.name)}</div>
                        <div class="text-muted fs-7">${user.uuid?.substring(0, 8)}...</div>
                    </div>
                </div>
            `;
            row.insertCell(2).innerHTML = `
                <div><i class="ki-duotone ki-sms fs-5 me-1"><span class="path1"></span><span class="path2"></span></i> ${escapeHtml(user.email)}</div>
                ${user.phone ? `<div class="text-muted fs-7 mt-1"><i class="ki-duotone ki-call fs-5 me-1"><span class="path1"></span><span class="path2"></span></i> ${user.country_code || ''} ${user.phone}</div>` : ''}
            `;
            row.insertCell(3).innerHTML = user.roles.map(r => `<span class="badge badge-light-primary fs-7 m-1">${formatRoleName(r)}</span>`).join('') || '<span class="badge badge-light-secondary">No Role</span>';
            row.insertCell(4).innerHTML = user.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
            row.insertCell(5).innerHTML = user.last_login_at || '<span class="text-muted">Never</span>';
            row.insertCell(6).innerHTML = user.created_at;
            row.insertCell(7).innerHTML = `
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="openPermissionsModal(${user.id}, '${escapeHtml(user.name)}', ${JSON.stringify(user.roles).replace(/"/g, '&quot;')})" title="Permissions" style="width: 32px; height: 32px;">
                        <i class="ki-duotone ki-shield fs-3"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="toggleUserStatus(${user.id}, ${user.is_active})" title="${user.is_active ? 'Deactivate' : 'Activate'}" style="width: 32px; height: 32px;">
                        <i class="ki-duotone ki-${user.is_active ? 'disconnect' : 'check'} fs-3"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editUser(${user.id})" title="Edit" style="width: 32px; height: 32px;">
                        <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="viewUser(${user.id})" title="View" style="width: 32px; height: 32px;">
                        <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                    ${!user.roles.includes('super_admin') ? `
                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Delete" style="width: 32px; height: 32px;">
                            <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        </button>
                    ` : `
                        <button type="button" class="btn btn-sm btn-icon btn-light" disabled title="Super Admin cannot be deleted" style="width: 32px; height: 32px; opacity: 0.5;">
                            <i class="ki-duotone ki-shield fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    `}
                </div>
            `;
        });
    }
    
    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        if (!el) return;
        
        el.innerHTML = '';
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
        
        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) {
                a.onclick = (e) => {
                    e.preventDefault();
                    changePage(page);
                };
            }
            li.appendChild(a);
            el.appendChild(li);
        };
        
        addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
        
        let start = Math.max(1, data.current_page - 2);
        let end = Math.min(data.last_page, data.current_page + 2);
        
        if (start > 1) addPage(1, '1');
        if (start > 2) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = '<span class="page-link">...</span>';
            el.appendChild(dots);
        }
        
        for (let i = start; i <= end; i++) {
            addPage(i, i, i === data.current_page);
        }
        
        if (end < data.last_page - 1) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = '<span class="page-link">...</span>';
            el.appendChild(dots);
        }
        if (end < data.last_page) addPage(data.last_page, data.last_page);
        
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }
    
    window.changePage = function(page) {
        if (page !== currentPage && page > 0) {
            currentPage = page;
            loadUsers();
            document.getElementById('usersTable')?.scrollIntoView({ behavior: 'smooth' });
        }
    };
    
    window.openPermissionsModal = function(userId, userName, userRoles) {
        currentUserId = userId;
        document.getElementById('permUserName').innerHTML = userName;
        document.getElementById('permUserRole').innerHTML = `Roles: ${userRoles.map(r => formatRoleName(r)).join(', ') || 'No roles'}`;
        
        fetch(`/admin/users/${userId}/permissions`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                
                const rolePermsDiv = document.getElementById('rolePermissionsList');
                rolePermsDiv.innerHTML = data.role_permissions.length ? data.role_permissions.map(p => `<span class="badge badge-light-info fs-7 m-1">${formatRoleName(p)}</span>`).join('') : '<span class="text-muted">No inherited permissions</span>';
                
                const directPermsDiv = document.getElementById('directPermissionsList');
                directPermsDiv.innerHTML = data.direct_permissions.length ? data.direct_permissions.map(p => `<span class="badge badge-light-success fs-7 m-1">${formatRoleName(p)} <i class="ki-duotone ki-cross-circle ms-2 cursor-pointer" style="cursor:pointer" onclick="revokePermission('${p}')"><span class="path1"></span><span class="path2"></span></i></span>`).join('') : '<span class="text-muted">No direct permissions</span>';
                
                allPermissionsList = data.all_permissions;
                const select = document.getElementById('assignPermissionSelect');
                const existingPerms = [...data.direct_permissions, ...data.role_permissions];
                select.innerHTML = '<option value="">Select Permission</option>' + data.all_permissions.filter(p => !existingPerms.includes(p.name)).map(p => `<option value="${p.name}">${formatRoleName(p.name)}</option>`).join('');
                
                const assignBtn = document.getElementById('assignPermissionBtn');
                assignBtn.onclick = () => assignPermission();
                
                // Ensure any existing modal backdrop is removed before showing new one
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                const modal = new bootstrap.Modal(document.getElementById('kt_modal_user_permissions'));
                modal.show();
            })
            .catch(err => window.showToast('error', 'Failed to load permissions'));
    };
    
    window.assignPermission = function() {
        const select = document.getElementById('assignPermissionSelect');
        const permission = select.value;
        if (!permission) return window.showToast('warning', 'Please select a permission');
        
        const btn = document.getElementById('assignPermissionBtn');
        window.showButtonSpinner(btn);
        
        fetch(`/admin/users/${currentUserId}/assign-permission`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ permission })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                // Close the modal immediately
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_user_permissions'));
                if (modal) {
                    modal.hide();
                }
                // Remove any modal backdrops that might be left
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
                // Reload users to update any permission-related changes
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to assign permission'))
        .finally(() => {
            window.hideButtonSpinner(btn);
            // Reset the select
            select.value = '';
        });
    };
        
    window.revokePermission = function(permission) {
        if (!confirm(`Revoke "${formatRoleName(permission)}" from this user?`)) return;
        
        fetch(`/admin/users/${currentUserId}/revoke-permission`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ permission })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                // Close the modal immediately
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_user_permissions'));
                if (modal) {
                    modal.hide();
                }
                // Remove any modal backdrops that might be left
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
                // Reload users to update any permission-related changes
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        });
    };
    
    window.toggleUserStatus = function(id, current) {
        const action = current ? 'deactivate' : 'activate';
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            fetch(`/admin/users/${id}/toggle-status`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showToast('success', data.message);
                    loadUsers();
                } else {
                    window.showToast('error', data.message);
                }
            });
        }
    };
    
    window.editUser = function(id) {
        fetch(`/admin/users/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return window.showToast('error', data.message);
                document.getElementById('edit_user_id').value = data.id;
                document.getElementById('edit_first_name').value = data.first_name;
                document.getElementById('edit_last_name').value = data.last_name;
                document.getElementById('edit_email').value = data.email;
                document.getElementById('edit_phone').value = data.phone || '';
                document.getElementById('edit_country_code').value = data.country_code || '+254';
                document.getElementById('edit_role_select').value = data.role || '';
                new bootstrap.Modal(document.getElementById('kt_modal_edit_user')).show();
            });
    };
    
    window.deleteUser = function(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
            fetch(`/admin/users/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showToast('success', data.message);
                    loadUsers();
                } else {
                    window.showToast('error', data.message);
                }
            });
        }
    };
    
    document.getElementById('addUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('addUserBtn');
        window.showButtonSpinner(btn);
        
        fetch('{{ route("users.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_user'))?.hide();
                this.reset();
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to create user'))
        .finally(() => window.hideButtonSpinner(btn));
    });
    
    document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('editUserBtn');
        window.showButtonSpinner(btn);
        const id = document.getElementById('edit_user_id').value;
        
        fetch(`/admin/users/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_user'))?.hide();
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to update user'))
        .finally(() => window.hideButtonSpinner(btn));
    });
    
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }


    let currentViewUserId = null;

    window.viewUser = function (id) {
        currentViewUserId = id;

        fetch(`/admin/users/${id}/detail`)
            .then(res => res.json())
            .then(resp => {
                if (!resp.success) return window.showToast('error', resp.message);
                const u = resp.data;

                // Header
                document.getElementById('view_user_avatar').src = u.avatar || '{{ asset("assets/media/avatars/blank.png") }}';
                document.getElementById('view_user_name').textContent = u.name || '(no name)';
                document.getElementById('view_user_email').textContent = u.email || '';
                document.getElementById('view_user_uuid').textContent = u.uuid || '';
                document.getElementById('view_user_platform_badge').classList.toggle('d-none', !u.is_platform_admin);

                document.getElementById('view_user_status').innerHTML = u.is_active
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';

                document.getElementById('view_user_2fa_badge').innerHTML = u.has_two_factor
                    ? '<span class="badge badge-light-primary">2FA on</span>'
                    : '<span class="badge badge-light-secondary">2FA off</span>';

                // Overview
                document.getElementById('view_user_email_2').textContent = u.email || '—';
                document.getElementById('view_user_phone').textContent = u.phone || '—';
                document.getElementById('view_user_cc').textContent = u.country_code || '—';
                document.getElementById('view_user_created').textContent = u.created_at || '—';
                document.getElementById('view_user_last_login').textContent = u.last_login_at || 'Never';
                document.getElementById('view_user_last_ip').textContent = u.last_login_ip || '—';
                document.getElementById('view_user_company').textContent = u.current_company
                    ? `${u.current_company.name} (${u.current_company.public_id})` : '—';
                document.getElementById('view_user_mode').innerHTML = u.current_mode
                    ? `<span class="badge badge-light-${u.current_mode === 'live' ? 'danger' : 'info'}">${u.current_mode}</span>`
                    : '—';
                document.getElementById('view_user_locale').textContent =
                    [u.locale, u.timezone].filter(Boolean).join(' / ') || '—';
                document.getElementById('view_user_terms').textContent = u.terms_accepted_at || 'Not accepted';

                // Security
                document.getElementById('view_user_2fa_tab').textContent = u.has_two_factor ? 'Yes' : 'No';
                document.getElementById('view_user_2fa_method').textContent = u.two_factor_method || '—';
                document.getElementById('view_user_2fa_confirmed').textContent = u.two_factor_confirmed_at || '—';
                document.getElementById('view_user_failed_attempts').textContent = u.failed_login_attempts ?? 0;
                document.getElementById('view_user_locked').innerHTML = u.is_locked
                    ? `<span class="badge badge-light-danger">Until ${u.locked_until}</span>`
                    : '<span class="badge badge-light-success">No</span>';
                document.getElementById('view_user_pw_changed').textContent = u.password_changed_at || '—';

                // RBAC
                document.getElementById('view_user_roles').innerHTML = u.roles.length
                    ? u.roles.map(r => `<span class="badge badge-light-primary">${formatRoleName(r)}</span>`).join('')
                    : '<span class="text-muted">No roles</span>';

                document.getElementById('view_user_role_perms').innerHTML = u.role_permissions.length
                    ? u.role_permissions.map(p => `<span class="badge badge-light-info fs-8">${formatRoleName(p)}</span>`).join('')
                    : '<span class="text-muted">No inherited permissions</span>';

                document.getElementById('view_user_direct_perms').innerHTML = u.direct_permissions.length
                    ? u.direct_permissions.map(p => `<span class="badge badge-light-success fs-8">${formatRoleName(p)}</span>`).join('')
                    : '<span class="text-muted">No direct permissions</span>';

                // Devices
                const devicesEmpty = document.getElementById('view_user_devices_empty');
                const devicesContainer = document.getElementById('view_user_devices_container');
                const devicesBody = document.getElementById('view_user_devices_body');
                devicesBody.innerHTML = '';

                if (!u.devices.length) {
                    devicesEmpty.classList.remove('d-none');
                    devicesContainer.classList.add('d-none');
                } else {
                    devicesEmpty.classList.add('d-none');
                    devicesContainer.classList.remove('d-none');
                    u.devices.forEach(d => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${escapeHtml(d.device_name ?? '—')}</td>
                            <td>${escapeHtml([d.platform, d.browser].filter(Boolean).join(' · ') || '—')}</td>
                            <td>${escapeHtml(d.ip_address ?? '—')}</td>
                            <td>${escapeHtml(d.last_active_at ?? '—')}</td>
                            <td>${d.is_trusted ? '<span class="badge badge-light-success">Trusted</span>' : '<span class="badge badge-light-secondary">—</span>'}</td>
                        `;
                        devicesBody.appendChild(tr);
                    });
                }

                // Wire the action buttons
                const unlockBtn = document.getElementById('view_user_unlock_btn');
                unlockBtn.disabled = !u.is_locked;
                unlockBtn.onclick = () => unlockUser(u.id);

                const platformBtn = document.getElementById('view_user_toggle_platform_btn');
                platformBtn.disabled = u.roles.includes('super_admin');
                platformBtn.onclick = () => togglePlatformAdmin(u.id);

                new bootstrap.Modal(document.getElementById('kt_modal_view_user')).show();
            })
            .catch(err => {
                console.error(err);
                window.showToast('error', 'Failed to load user details');
            });
    };

    window.unlockUser = function (id) {
        if (!confirm('Clear lockout and reset failed attempts?')) return;

        fetch(`/admin/users/${id}/unlock`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                viewUser(id); // refresh modal content
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to unlock user'));
    };

    window.togglePlatformAdmin = function (id) {
        if (!confirm('Toggle platform staff flag for this user?')) return;

        fetch(`/admin/users/${id}/toggle-platform-admin`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                viewUser(id); // refresh modal content
                loadUsers();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to update flag'));
    };
</script>
@endpush