@props([
    'title' => null,
])

@php
    $themeBase = 'DashboardKit-main';
@endphp

<title>{{ $title ? $title.' - ' : '' }}{{ config('app.name') }}</title>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />

<link rel="icon" href="{{ asset($themeBase.'/images/favicon.svg') }}" type="image/x-icon">

<link rel="stylesheet" href="{{ asset($themeBase.'/css/feather.css') }}">
<link rel="stylesheet" href="{{ asset($themeBase.'/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset($themeBase.'/css/material.css') }}">
<link rel="stylesheet" href="{{ asset($themeBase.'/css/style.css') }}" id="main-style-link">
<link rel="stylesheet" href="{{ asset($themeBase.'/css/custom.css') }}">

<style>
    :root {
        --brand-primary: {{ $webCustomization['primaryColor'] ?? '#751204' }};
        --brand-primary-rgb: {{ $webCustomization['primaryRgb'] ?? '117, 18, 4' }};
        --brand-primary-dark: {{ $webCustomization['primaryDark'] ?? '#610000' }};
    }

    .btn-primary,
    .btn-check:checked + .btn-primary,
    .btn-check:active + .btn-primary,
    .btn-primary:active,
    .btn-primary.active,
    .show > .btn-primary.dropdown-toggle {
        background-color: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }

    .btn-primary:hover,
    .btn-primary:focus {
        background-color: var(--brand-primary-dark) !important;
        border-color: var(--brand-primary-dark) !important;
    }

    .btn-primary:focus,
    .btn-check:focus + .btn-primary {
        box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.35) !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand-primary) !important;
        box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.25) !important;
    }

    .pc-sidebar,
    .pc-sidebar .m-header,
    .pc-mob-header.pc-header {
        background: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }

    .pc-sidebar * {
        --bs-primary: var(--brand-primary) !important;
    }
</style>

@stack('styles')
