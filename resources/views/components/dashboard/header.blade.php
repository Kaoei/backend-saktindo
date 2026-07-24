@php
    $themeBase = 'DashboardKit-main';
    $user = auth()->user();

    $initials = 'U';
    if (!empty($user?->name)) {
        $words = preg_split('/\s+/', trim($user->name));
        if (count($words) >= 2) {
            $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        } else {
            $initials = mb_strtoupper(mb_substr($words[0], 0, 2));
        }
    }
@endphp

<div class="pc-mob-header pc-header">
    <div class="pcm-left">
        <a href="#!" class="pc-head-link" id="mobile-collapse" title="Menu Sidebar">
            <i data-feather="menu"></i>
        </a>
    </div>
    <div class="pcm-logo">
        <a href="{{ route('dashboard') }}" class="b-brand">
            <img src="{{ $webCustomization['sidebarLogoUrl'] ?? asset('src/img/gapuraWhite.png') }}"
                 alt="Sidebar logo"
                 class="logo logo-lg"
                 onerror="this.onerror=null; this.src='{{ asset('src/img/gapuraWhite.png') }}';"
                 style="max-width: 100%; max-height: 42px; width: auto; height: auto; object-fit: contain;">
        </a>
    </div>
    <div class="pcm-toolbar">
        @auth
            <div class="dropdown">
                <a href="#" class="pc-head-link dropdown-toggle arrow-none mr-0" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="Menu User">
                    <div class="user-avtar-initials sm">{{ $initials }}</div>
                </a>
                <div class="dropdown-menu dropdown-menu-right pc-h-dropdown">
                    <div class="dropdown-header d-flex align-items-center p-3">
                        <div class="user-avtar-initials mr-2" style="width: 36px; height: 36px; font-size: 0.85rem;">{{ $initials }}</div>
                        <div>
                            <h6 class="text-overflow m-0" style="font-size: 0.9rem; font-weight: 600;">{{ $user?->name }}</h6>
                            <small class="text-muted">{{ $user?->email }}</small>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="material-icons-two-tone">logout</i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</div>

<header class="pc-header ">
    <div class="header-wrapper">
        <div class="mr-auto pc-mob-drp"></div>

        <div class="ml-auto">
            <ul class="list-unstyled">
                @auth
                    <li class="dropdown pc-h-item">
                        <a class="pc-head-link dropdown-toggle arrow-none mr-0" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <div class="user-avtar-initials">{{ $initials }}</div>
                            <span>
                                <span class="user-name">{{ $user?->name }}</span>
                                <span class="user-desc">{{ $user?->role_label }}</span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right pc-h-dropdown">
                            <div class="dropdown-header d-flex align-items-center p-3">
                                <div class="user-avtar-initials mr-2" style="width: 36px; height: 36px; font-size: 0.85rem;">{{ $initials }}</div>
                                <div>
                                    <h6 class="text-overflow m-0" style="font-size: 0.9rem; font-weight: 600;">{{ $user?->name }}</h6>
                                    <small class="text-muted">{{ $user?->email }}</small>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="material-icons-two-tone">logout</i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</header>
