<div id="kt_app_sidebar" class="app-sidebar flex-column"
    data-kt-drawer="true"
    data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="225px"
    data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    {{-- Logo --}}
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <a href="{{ route('admin.dashboard') }}">
            <span class="app-sidebar-logo-default" style="align-items:center; gap:6px;">
                <img alt="Logo" src="{{ asset('pay.png') }}"
                    style="height:45px; width:auto; display:inline-block; vertical-align:middle;" />
                <span
                    style="
                        font-family:'Archivo','Inter',system-ui,sans-serif;
                        font-weight:700;
                        font-size:45px;
                        letter-spacing:-.01em;
                        line-height:1;
                        color:#6E3FE7;
                        background:linear-gradient(120deg, #5A2FD8 0%, #6E3FE7 45%, #9F7BFF 100%);
                        -webkit-background-clip:text;
                        background-clip:text;
                        -webkit-text-fill-color:transparent;
                        display:inline-block;
                        vertical-align:middle;
                    "
                >Pay</span>
            </span>
            <img alt="Logo" src="{{ asset('pay.png') }}"
                class="app-sidebar-logo-minimize"
                style="height:30px; width:30px;" />
        </a>
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true"
            data-kt-toggle-state="active"
            data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-duotone ki-black-left-line fs-3 rotate-180">
                <span class="path1"></span><span class="path2"></span>
            </i>
        </div>
    </div>

    {{-- Menu --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3"
                data-kt-scroll="true"
                data-kt-scroll-activate="true"
                data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu"
                data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">

                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6"
                    id="#kt_app_sidebar_menu"
                    data-kt-menu="true"
                    data-kt-menu-expand="false">

                    {{-- ───────────── DASHBOARD ───────────── --}}
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-element-11 fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                    <span class="path3"></span><span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </div>

                     {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- PLATFORM SECTION — visible to platform-side roles only     --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}

                    @canany([
                        'view companies', 'view all payments', 'view all refunds', 'view all payouts',
                        'view all invoices', 'view all customers', 'view all subscriptions',
                        'view ledger', 'view reconciliation',
                        'view risk assessments', 'manage blocklist', 'view disputes',
                        'view providers', 'view routing rules', 'view fee schedules', 'view company compliance',
                    ])
                        <div class="menu-item pt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7">Platform</span>
                            </div>
                        </div>

                        
                        {{-- Companies / Merchants --}}
                        @can('view companies')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-briefcase fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Companies</span>
                                </a>
                            </div>
                        @endcan

                        @can('view company compliance')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.compliance.*') ? 'active' : '' }}" href="{{ route('admin.compliance.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-shield-tick fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    </span>
                                    <span class="menu-title">Verification Queue</span>
                                    @php $pendingCount = \App\Models\Company\Company::where('kyb_status','pending')->count(); @endphp
                                    @if($pendingCount)
                                        <span class="badge badge-light-warning ms-2">${{ $pendingCount }}</span>
                                    @endif
                                </a>
                            </div>
                        @endcan

                        @can('view payment links')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.company2.*') ? 'active' : '' }}" href="{{ route('admin.company2.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-link fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    </span>
                                    <span class="menu-title">Payment Links</span>
                                </a>
                            </div>
                        @endcan


                        
                    @endcanany


                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- SETTINGS — Reference Data + System Settings                 --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}

                    @canany(['view countries', 'view currencies', 'view exchange rates', 'manage system settings', 'view audit logs'])
                        <div class="menu-item pt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7">Settings</span>
                            </div>
                        </div>

                        {{-- ═══════════ MANAGEMENT — users/roles/permissions ═══════════ --}}
                        @canany(['view users', 'view roles', 'view permissions'])
                            <div class="menu-item pt-5">
                                <div class="menu-content">
                                    <span class="menu-heading fw-bold text-uppercase fs-7">Management</span>
                                </div>
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('users.*', 'admin.roles', 'admin.roles.*', 'admin.permissions', 'admin.permissions.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon"><i class="ki-duotone ki-abstract-28 fs-2"><span class="path1"></span><span class="path2"></span></i></span>
                                    <span class="menu-title">User Management</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view users')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Users List</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view roles')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.roles', 'admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Roles</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view permissions')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.permissions', 'admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Permissions</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        {{-- Reference Data — visible to anyone with view permission on any of the three --}}
                        @canany(['view countries', 'view currencies', 'view exchange rates'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('countries.*', 'currencies.*', 'exchange-rates.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-file-sheet fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Reference Data</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view countries')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('countries.*') ? 'active' : '' }}" href="{{ route('countries.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Countries</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view currencies')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('currencies.*') ? 'active' : '' }}" href="{{ route('currencies.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Currencies</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view exchange rates')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('exchange-rates.*') ? 'active' : '' }}" href="{{ route('exchange-rates.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Exchange Rates</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                    @endcanany

                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar footer --}}
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-40px w-100">
                <span class="btn-label">Sign Out</span>
                <i class="ki-duotone ki-entrance-right btn-icon fs-2 m-0">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </button>
        </form>
    </div>

</div>