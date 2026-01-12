@php
    $themeBase = 'DashboardKit-main';
@endphp

<nav class="pc-sidebar ">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ asset($themeBase.'/images/logo.svg') }}" alt="" class="logo logo-lg">
                <img src="{{ asset($themeBase.'/images/logo-sm.svg') }}" alt="" class="logo logo-sm">
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
                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <li class="pc-item pc-caption">
                            <label>Management</label>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('users.index') }}" class="pc-link ">
                                <span class="pc-micon"><i class="material-icons-two-tone">group</i></span>
                                <span class="pc-mtext">User Management</span>
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav>
