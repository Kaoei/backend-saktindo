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

@stack('styles')
