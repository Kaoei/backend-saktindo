<!DOCTYPE html>
<html lang="en">
<head>
    <x-dashboard.head :title="$title ?? null" />
</head>

<body class="">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <x-dashboard.header />
    <x-dashboard.sidebar />

    <div class="pc-container">
        <div class="pcoded-content">
            @isset($pageTitle)
                <div class="page-header">
                    <div class="page-block">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="page-header-title">
                                    <h5 class="m-b-10">{{ $pageTitle }}</h5>
                                </div>
                                @isset($breadcrumb)
                                    <ul class="breadcrumb">{!! $breadcrumb !!}</ul>
                                @endisset
                            </div>
                        </div>
                    </div>
                </div>
            @endisset

            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </div>

    <x-dashboard.scripts />
    @stack('scripts')
</body>
</html>
