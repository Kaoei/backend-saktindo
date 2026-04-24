@extends('layouts.dashboard', [
    'title' => 'Web Customization',
    'pageTitle' => 'Web Customization',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Web Customization</li>',
])

@php
    $palette = [
        '#751204',
        '#0F766E',
        '#1D4ED8',
        '#9A3412',
        '#374151',
        '#BE123C',
        '#0C4A6E',
        '#4C1D95',
    ];
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customize Branding</h5>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('web-customization.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="primary_color" class="form-label fw-semibold">Main Color</label>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            @foreach ($palette as $color)
                                <button
                                    type="button"
                                    class="btn p-0 border rounded color-palette"
                                    style="width: 32px; height: 32px; background: {{ $color }};"
                                    data-color="{{ $color }}"
                                    title="{{ $color }}"
                                ></button>
                            @endforeach
                        </div>

                        <div class="input-group" style="max-width: 260px;">
                            <span class="input-group-text p-1">
                                <input type="color" id="primary_color_picker" value="{{ old('primary_color', $webCustomization['primaryColor']) }}" style="width: 30px; height: 30px; border: none; background: transparent;">
                            </span>
                            <input
                                type="text"
                                name="primary_color"
                                id="primary_color"
                                class="form-control"
                                value="{{ old('primary_color', $webCustomization['primaryColor']) }}"
                                placeholder="#751204"
                                required
                            >
                        </div>
                        <small class="text-muted">Pilih dari palette atau isi manual format HEX, contoh: #751204.</small>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sidebar Icon / Logo</label>
                            <div class="border rounded p-3 bg-dark mb-2 text-center">
                                <img src="{{ $webCustomization['sidebarLogoUrl'] }}" alt="Sidebar logo preview" style="max-height: 80px; max-width: 100%;">
                            </div>
                            <input type="file" name="sidebar_logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Login Page Icon / Logo</label>
                            <div class="border rounded p-3 bg-light mb-2 text-center">
                                <img src="{{ $webCustomization['loginLogoUrl'] }}" alt="Login logo preview" style="max-height: 80px; max-width: 100%;">
                            </div>
                            <input type="file" name="login_logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                        </div>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary">Simpan Customization</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const colorInput = document.getElementById('primary_color');
        const colorPicker = document.getElementById('primary_color_picker');
        const paletteButtons = document.querySelectorAll('.color-palette');

        const normalizeHex = (value) => {
            const match = String(value).trim().match(/^#?[0-9a-fA-F]{6}$/);
            if (!match) return null;
            const hex = match[0].replace('#', '').toUpperCase();
            return '#' + hex;
        };

        paletteButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const selected = button.getAttribute('data-color');
                colorInput.value = selected;
                colorPicker.value = selected;
            });
        });

        colorPicker.addEventListener('input', (event) => {
            colorInput.value = event.target.value.toUpperCase();
        });

        colorInput.addEventListener('blur', () => {
            const normalized = normalizeHex(colorInput.value);
            if (normalized) {
                colorInput.value = normalized;
                colorPicker.value = normalized;
            }
        });
    })();
</script>
@endpush
