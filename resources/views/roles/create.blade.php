@extends('layouts.dashboard', [
    'title' => 'Tambah Role',
    'pageTitle' => 'Role Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('roles.index').'">Role Management</a></li><li class="breadcrumb-item">Tambah Role</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Tambah Role Baru</h5>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Contoh: Kepala Sales" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Slug akan dibuat otomatis dari nama.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <input name="description" type="text" class="form-control @error('description') is-invalid @enderror"
                                   value="{{ old('description') }}" placeholder="Deskripsi singkat role ini...">
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3 text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                        <i class="material-icons-two-tone me-1" style="font-size:14px;">security</i>
                        Permissions / Fitur yang Dapat Diakses
                    </h6>

                    @foreach($permissionGroups as $groupName => $groupPerms)
                        <div class="card border mb-3">
                            <div class="card-header py-2 d-flex align-items-center justify-content-between bg-light">
                                <span class="fw-semibold">{{ $groupName }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0"
                                        onclick="toggleGroup('{{ Str::slug($groupName) }}')">
                                    Pilih Semua
                                </button>
                            </div>
                            <div class="card-body py-2">
                                <div class="row" id="group-{{ Str::slug($groupName) }}">
                                    @foreach($groupPerms as $perm)
                                        <div class="col-lg-4 col-md-6 py-1">
                                            <div class="form-check">
                                                <input class="form-check-input perm-{{ Str::slug($groupName) }}"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm }}"
                                                       id="perm_{{ Str::slug($perm) }}"
                                                       {{ in_array($perm, old('permissions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm_{{ Str::slug($perm) }}">
                                                    {{ $permissions[$perm] ?? $perm }}
                                                    <br><small class="text-muted font-monospace" style="font-size:10px;">{{ $perm }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Simpan Role</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleGroup(groupSlug) {
        const checkboxes = document.querySelectorAll('.perm-' + groupSlug);
        const allChecked = Array.from(checkboxes).every(c => c.checked);
        checkboxes.forEach(c => c.checked = !allChecked);
    }
</script>
@endpush
