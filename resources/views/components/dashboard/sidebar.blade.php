@php
    $themeBase = 'DashboardKit-main';
@endphp

<nav class="pc-sidebar ">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ $webCustomization['sidebarLogoUrl']  }}" alt="Sidebar logo" class="logo" style="height: 12rem;">
                <img src="{{ $webCustomization['sidebarLogoUrl']  }}" alt="Sidebar logo small" class="logo logo-sm">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item pc-caption">
                    <label><i class="material-icons-two-tone me-1" style="font-size: 16px;">explore</i>Navigation</label>
                </li>

                @if(auth()->user()?->hasPermission('dashboard'))
                    <li class="pc-item">
                        <a href="{{ route('dashboard') }}" class="pc-link ">
                            <span class="pc-micon"><i class="material-icons-two-tone">home</i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>
                @endif

                <li class="pc-item">
                    <a href="{{ route('products.index') }}" class="pc-link {{ Request::routeIs('products.*') ? 'active' : '' }}">
                        <span class="pc-micon"><i class="material-icons-two-tone">shopping_bag</i></span>
                        <span class="pc-mtext">Product Management</span>
                    </a>
                </li>

                @auth
                    @if(auth()->user()?->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN]))
                        <li class="pc-item pc-caption">
                            <label><i class="material-icons-two-tone me-1" style="font-size: 16px;">groups</i>Customer</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">supervised_user_circle</i>
                                </span>
                                <span class="pc-mtext">Customer Management</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('master-customer.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">List Customer</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user()?->hasPermission('suppliers.view'))
                        <li class="pc-item pc-caption">
                            <label><i class="material-icons-two-tone me-1" style="font-size: 16px;">folder_special</i>Master</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon"><i class="material-icons-two-tone">local_shipping</i></span>
                                <span class="pc-mtext">Supplier</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>
                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">dashboard</i></span>
                                        <span class="pc-mtext">Overview</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.index') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">business</i></span>
                                        <span class="pc-mtext">Data Vendor</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.products') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">inventory_2</i></span>
                                        <span class="pc-mtext">Barang Supplier</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.contacts') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">contacts</i></span>
                                        <span class="pc-mtext">Kontak Supplier</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.purchases') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">receipt_long</i></span>
                                        <span class="pc-mtext">Riwayat Pembelian</span>
                                    </a>
                                </li>
                                <li class="pc-item">
                                    <a href="{{ route('suppliers.payment-terms') }}" class="pc-link">
                                        <span class="pc-micon"><i class="material-icons-two-tone">payments</i></span>
                                        <span class="pc-mtext">Termin Pembayaran</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user()?->hasPermission('users.view') || auth()->user()?->hasPermission('roles.manage') || auth()->user()?->hasPermission('activity_logs.view') || auth()->user()?->hasPermission('sessions.manage'))
                        <li class="pc-item pc-caption">
                            <label><i class="material-icons-two-tone me-1" style="font-size: 16px;">manage_accounts</i>Management</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">admin_panel_settings</i>
                                </span>
                                <span class="pc-mtext">User & Role Management</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                @if(auth()->user()?->hasPermission('users.view'))
                                    <li class="pc-item">
                                        <a href="{{ route('users.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">group</i>
                                            </span>
                                            <span class="pc-mtext">User Management</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('roles.manage'))
                                    <li class="pc-item">
                                        <a href="{{ route('roles.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">admin_panel_settings</i>
                                            </span>
                                            <span class="pc-mtext">Role Management</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('activity_logs.view'))
                                    <li class="pc-item">
                                        <a href="{{ route('activity-logs.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">history</i>
                                            </span>
                                            <span class="pc-mtext">Activity Log</span>
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()?->hasPermission('sessions.manage'))
                                    <li class="pc-item">
                                        <a href="{{ route('sessions.index') }}" class="pc-link">
                                            <span class="pc-micon">
                                                <i class="material-icons-two-tone">devices</i>
                                            </span>
                                            <span class="pc-mtext">Session Management</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        @if(auth()->user()?->hasPermission('roles.manage'))
                            <li class="pc-item">
                                <a href="{{ route('web-customization.edit') }}" class="pc-link ">
                                    <span class="pc-micon"><i class="material-icons-two-tone">computer</i></span>
                                    <span class="pc-mtext">Web Customization</span>
                                </a>
                            </li>
                        @endif
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav>
