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
                        'view providers', 'view routing rules', 'view fee schedules',
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

                        {{-- Payments oversight --}}
                        @can('view all payments')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-credit-cart fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">All Payments</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Refunds --}}
                        @can('view all refunds')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-arrow-circle-left fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">All Refunds</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Payouts / settlements --}}
                        @can('view all payouts')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}" href="{{ route('admin.payouts.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-wallet fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">All Payouts</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Ledger --}}
                        @can('view ledger')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.ledger.*') ? 'active' : '' }}" href="{{ route('admin.ledger.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-book fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Ledger</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Compliance --}}
                        @canany(['view company compliance', 'view risk assessments', 'view disputes', 'manage blocklist'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.compliance.*', 'admin.risk.*', 'admin.disputes.*', 'admin.blocklist.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-shield-tick fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Compliance</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view company compliance')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.compliance.*') ? 'active' : '' }}" href="{{ route('admin.compliance.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Verification Queue</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view disputes')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}" href="{{ route('admin.disputes.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Disputes</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view risk assessments')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.risk.*') ? 'active' : '' }}" href="{{ route('admin.risk.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Risk Assessments</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('manage blocklist')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.blocklist.*') ? 'active' : '' }}" href="{{ route('admin.blocklist.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Blocklist</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        {{-- Providers & Routing --}}
                        @canany(['view providers', 'view routing rules'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.providers.*', 'admin.routing-rules.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-abstract-39 fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Providers</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view providers')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.providers.*') ? 'active' : '' }}" href="{{ route('admin.providers.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Payment Providers</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view routing rules')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.routing-rules.*') ? 'active' : '' }}" href="{{ route('admin.routing-rules.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Routing Rules</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        {{-- Fee Schedules --}}
                        @can('view fee schedules')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.fee-schedules.*') ? 'active' : '' }}" href="{{ route('admin.fee-schedules.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-percentage fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Fee Schedules</span>
                                </a>
                            </div>
                        @endcan
                    @endcanany

                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- MERCHANT SECTION — visible to merchant-side roles only     --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}

                    @canany(['view customers', 'view invoices', 'view payment links', 'view payments', 'view balance'])
                        <div class="menu-item pt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7">My Business</span>
                            </div>
                        </div>

                        {{-- Customers --}}
                        @can('view customers')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-profile-circle fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Customers</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Catalog --}}
                        @canany(['view products', 'view prices', 'view discounts', 'view tax rates'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.products.*', 'admin.prices.*', 'admin.discounts.*', 'admin.tax-rates.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-package fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Catalog</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view products')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Products</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view prices')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.prices.*') ? 'active' : '' }}" href="{{ route('admin.prices.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Prices</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view discounts')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.discounts.*') ? 'active' : '' }}" href="{{ route('admin.discounts.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Discounts</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view tax rates')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.tax-rates.*') ? 'active' : '' }}" href="{{ route('admin.tax-rates.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Tax Rates</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        {{-- Payment Links --}}
                        @can('view payment links')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.payment-links.*') ? 'active' : '' }}" href="{{ route('admin.payment-links.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-link fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Payment Links</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Invoices --}}
                        @can('view invoices')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-document fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Invoices</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Subscriptions --}}
                        @can('view subscriptions')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-repeat fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Subscriptions</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Payments & Refunds --}}
                        @canany(['view payments', 'view refunds'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.payments.*', 'admin.refunds.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-credit-cart fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Payments</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view payments')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Transactions</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view refunds')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Refunds</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        {{-- Payouts --}}
                        @can('view payouts')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}" href="{{ route('admin.payouts.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-wallet fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Payouts</span>
                                </a>
                            </div>
                        @endcan

                        {{-- Balances / Reports --}}
                        @canany(['view balance', 'view reports'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.balance.*', 'admin.reports.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-chart-simple fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span><span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Reports</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view balance')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.balance.*') ? 'active' : '' }}" href="{{ route('admin.balance.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Balance</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view reports')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Reports</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany
                    @endcanany

                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- MANAGEMENT — Users / Roles / Permissions                    --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}

                    @canany(['view users', 'view roles', 'view permissions'])
                        <div class="menu-item pt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7">Management</span>
                            </div>
                        </div>

                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('users.*', 'admin.roles', 'admin.roles.*', 'admin.permissions', 'admin.permissions.*') ? 'show here' : '' }}">
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-abstract-28 fs-2">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </span>
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

                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- COMPANY — Team, API, Webhooks, Banks, Settings              --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}

                    @canany(['view company', 'view api keys', 'view webhooks', 'view company bank accounts'])
                        <div class="menu-item pt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7">Company</span>
                            </div>
                        </div>

                        @can('view company')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.company.*') ? 'active' : '' }}" href="{{ route('admin.company.show') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-briefcase fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Company Profile</span>
                                </a>
                            </div>
                        @endcan

                        @can('manage company team')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.company.team.*') ? 'active' : '' }}" href="{{ route('admin.company.team.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-profile-user fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span><span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Team</span>
                                </a>
                            </div>
                        @endcan

                        @canany(['view api keys', 'view webhooks'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.company.api-keys.*', 'admin.company.webhooks.*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-code fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span><span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Developers</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view api keys')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.company.api-keys.*') ? 'active' : '' }}" href="{{ route('admin.company.api-keys.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">API Keys</span>
                                            </a>
                                        </div>
                                    @endcan
                                    @can('view webhooks')
                                        <div class="menu-item">
                                            <a class="menu-link {{ request()->routeIs('admin.company.webhooks.*') ? 'active' : '' }}" href="{{ route('admin.company.webhooks.index') }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Webhooks</span>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        @can('view company bank accounts')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.company.bank-accounts.*') ? 'active' : '' }}" href="{{ route('admin.company.bank-accounts.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-bank fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Bank Accounts</span>
                                </a>
                            </div>
                        @endcan

                        @can('manage company settings')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.company.settings.*') ? 'active' : '' }}" href="{{ route('admin.company.settings.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-setting-2 fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Company Settings</span>
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

                        {{-- Audit Logs --}}
                        @can('view audit logs')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-time fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Audit Logs</span>
                                </a>
                            </div>
                        @endcan

                        {{-- System Settings --}}
                        @can('manage system settings')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-setting-2 fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">System Settings</span>
                                </a>
                            </div>
                        @endcan
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