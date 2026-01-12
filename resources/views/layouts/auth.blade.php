<!DOCTYPE html>
<html lang="en">
<head>
    <x-dashboard.head :title="$title ?? null" />
</head>
<body>
    {{ $slot ?? '' }}
    @yield('content')

    <x-dashboard.scripts />
    @stack('scripts')
</body>
</html>
