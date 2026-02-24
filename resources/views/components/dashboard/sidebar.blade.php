@php
    $themeBase = 'DashboardKit-main';
@endphp

<nav class="pc-sidebar ">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ asset('src/img/gapuraWhite.png') }}" alt="" class="logo" style="height: 12rem;">
                <img src="{{ asset('src/img/gapuraWhite.png') }}" alt="" class="logo logo-sm">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item pc-caption">
                    <label>Navigation</label>
                </li>

                <li class="pc-item">
                    <a href="{{ route('dashboard') }}" class="pc-link ">
                        <span class="pc-micon"><i class="material-icons-two-tone">home</i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                @auth
                    <li class="pc-item pc-caption">
                        <label>Sales</label>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('clients.index') }}" class="pc-link ">
                            <span class="pc-micon"><i class="material-icons-two-tone">contacts</i></span>
                            <span class="pc-mtext">Client Management</span>
                        </a>
                    </li>

                    @if(in_array(auth()->user()?->role, [\App\Models\User::ROLE_SALES, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
                        <li class="pc-item">
                            <a href="{{ route('proposals.index') }}" class="pc-link ">
                                <span class="pc-micon"><i class="material-icons-two-tone">description</i></span>
                                <span class="pc-mtext">Penawaran</span>
                            </a>
                        </li>
                    @endif

                    @if(in_array(auth()->user()?->role, [\App\Models\User::ROLE_FINANCE, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
                        <li class="pc-item pc-caption">
                            <label>Finance</label>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('invoices.index') }}" class="pc-link ">
                                <span class="pc-micon"><i class="material-icons-two-tone">receipt_long</i></span>
                                <span class="pc-mtext">Invoice</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <li class="pc-item pc-caption">
                            <label>Management</label>
                        </li>
                        <li class="pc-item pc-hasmenu">
                            <a href="javascript:void(0);" class="pc-link">
                                <span class="pc-micon">
                                    <i class="material-icons-two-tone">settings</i>
                                </span>
                                <span class="pc-mtext">User & Role Management</span>
                                <span class="pc-arrow">
                                    <i class="material-icons-two-tone text-white">chevron_right</i>
                                </span>
                            </a>

                            <ul class="pc-submenu">
                                <li class="pc-item">
                                    <a href="{{ route('users.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">group</i>
                                        </span>
                                        <span class="pc-mtext">User Management</span>
                                    </a>
                                </li>

                                <li class="pc-item">
                                    <a href="{{ route('roles.index') }}" class="pc-link">
                                        <span class="pc-micon">
                                            <i class="material-icons-two-tone">admin_panel_settings</i>
                                        </span>
                                        <span class="pc-mtext">Role Management</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav>