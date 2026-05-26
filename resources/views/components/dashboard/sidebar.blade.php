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
                    <label>Navigation</label>
                </li>

                <li class="pc-item">
                    <a href="{{ route('dashboard') }}" class="pc-link ">
                        <span class="pc-micon"><i class="material-icons-two-tone">home</i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="{{ route('products.index') }}" class="pc-link {{ Request::routeIs('products.*') ? 'active' : '' }}">
                        <span class="pc-micon"><i class="material-icons-two-tone">shopping_bag</i></span>
                        <span class="pc-mtext">Product Management</span>
                    </a>
                </li>

                @auth
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
                        <li class="pc-item">
                            <a href="{{ route('web-customization.edit') }}" class="pc-link ">
                                <span class="pc-micon"><i class="material-icons-two-tone">computer</i></span>
                                <span class="pc-mtext">Web Customization</span>
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav>