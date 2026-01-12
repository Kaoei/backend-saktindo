@php
    $themeBase = 'DashboardKit-main';
    $user = auth()->user();
@endphp

<div class="pc-mob-header pc-header">
    <div class="pcm-logo">
        <img src="{{ asset($themeBase.'/images/logo.svg') }}" alt="" class="logo logo-lg">
    </div>
    <div class="pcm-toolbar">
        <a href="#!" class="pc-head-link" id="mobile-collapse">
            <div class="hamburger hamburger--arrowturn">
                <div class="hamburger-box">
                    <div class="hamburger-inner"></div>
                </div>
            </div>
        </a>
        <a href="#!" class="pc-head-link" id="headerdrp-collapse">
            <i data-feather="align-right"></i>
        </a>
        <a href="#!" class="pc-head-link" id="header-collapse">
            <i data-feather="more-vertical"></i>
        </a>
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
                            <img src="{{ asset($themeBase.'/images/avatar-2.jpg') }}" alt="user-image" class="user-avtar">
                            <span>
                                <span class="user-name">{{ $user?->name }}</span>
                                <span class="user-desc">{{ $user?->role_label }}</span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right pc-h-dropdown">
                            <div class="dropdown-header">
                                <h6 class="text-overflow m-0">{{ $user?->email }}</h6>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="material-icons-two-tone">chrome_reader_mode</i>
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
