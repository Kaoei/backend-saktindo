@extends('layouts.dashboard', [
    'title' => 'Edit Role',
    'pageTitle' => 'Role Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('roles.index').'">Role Management</a></li><li class="breadcrumb-item">Edit Role</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Edit Role: {{ $role->name }}</h5>
                    <small class="text-muted">
                        Kode: <span class="font-monospace">{{ $role->id }}</span> &middot;
                        Slug: <code>{{ $role->slug }}</code>
                        @if($role->is_system)
                            &middot; <span class="badge bg-light-warning">Sistem</span>
                        @endif
                    </small>
                </div>
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
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $role->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($role->is_system)
                                <small class="text-warning">
                                    <i class="feather icon-alert-circle me-1"></i>
                                    Slug tidak berubah untuk role sistem.
                                </small>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <input name="description" type="text" class="form-control"
                                   value="{{ old('description', $role->description) }}"
                                   placeholder="Deskripsi singkat role ini...">
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                            <i class="material-icons-two-tone me-1" style="font-size:14px;">security</i>
                            Permissions / Fitur yang Dapat Diakses
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">Pilih Semua</button>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-1" onclick="deselectAll()">Hapus Pilihan</button>
                    </div>

                    @php $rolePerms = old('permissions', $role->permissions ?? []); @endphp

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
                                                <input class="form-check-input all-perm perm-{{ Str::slug($groupName) }}"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm }}"
                                                       id="perm_{{ Str::slug($perm) }}"
                                                       {{ in_array($perm, $rolePerms) ? 'checked' : '' }}>
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
                        <button type="submit" class="btn btn-primary">Update Role</button>
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
    function selectAll() {
        document.querySelectorAll('.all-perm').forEach(c => c.checked = true);
    }
    function deselectAll() {
        document.querySelectorAll('.all-perm').forEach(c => c.checked = false);
    }
</script>
@endpush
