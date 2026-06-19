@extends('layouts.dashboard', [
    'title' => 'Role Management',
    'pageTitle' => 'Role Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Role Management</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Daftar Role</h5>
                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                    <i class="material-icons-two-tone text-white">add_circle</i>
                    Tambah Role
                </a>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="roles-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Role</th>
                                <th>Slug</th>
                                <th>Deskripsi</th>
                                <th>Permissions</th>
                                <th>Tipe</th>
                                <th class="text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge bg-light-secondary font-monospace">{{ $role->id }}</span>
                                    </td>
                                    <td class="align-middle fw-semibold">{{ $role->name }}</td>
                                    <td class="align-middle">
                                        <code class="text-primary">{{ $role->slug }}</code>
                                    </td>
                                    <td class="align-middle text-muted">{{ $role->description ?? '-' }}</td>
                                    <td class="align-middle">
                                        <span class="badge bg-light-primary">
                                            {{ $role->permissions_count }} fitur
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        @if($role->is_system)
                                            <span class="badge bg-light-warning">Sistem</span>
                                        @else
                                            <span class="badge bg-light-info">Custom</span>
                                        @endif
                                    </td>
                                                    <td class="align-middle text-end">
                                        <a href="{{ route('roles.edit', $role) }}" class="text-success me-1" title="Edit">
                                            <i class="feather icon-edit f-16"></i>
                                        </a>
                                        @if($role->slug !== auth()->user()->role)
                                            <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger ms-1 btn-delete-role"
                                                title="Hapus Role"
                                                data-role-name="{{ $role->name }}"
                                                data-role-action="{{ route('roles.destroy', $role) }}">
                                                <i class="feather icon-trash-2 f-16"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn p-0 border-0 bg-transparent text-muted ms-1"
                                                    title="Tidak dapat menghapus role yang sedang Anda gunakan" disabled>
                                                <i class="feather icon-shield f-16"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Hapus Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Apakah Anda yakin ingin menghapus role <strong id="deleteRoleName"></strong>?</p>
                <p class="text-danger small mt-2 mb-0">
                    <i class="feather icon-alert-triangle me-1"></i>
                    Tindakan ini tidak dapat dibatalkan. User dengan role ini mungkin akan kehilangan akses.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="feather icon-trash-2 me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#roles-table').DataTable({
                pageLength: 25,
                order: [[5, 'desc'], [1, 'asc']],
                language: { emptyTable: 'Belum ada role.' },
                columnDefs: [{ orderable: false, targets: [6] }]
            });

            $(document).on('click', '.btn-delete-role', function () {
                const btn = this;
                document.getElementById('deleteRoleName').textContent = btn.dataset.roleName;
                document.getElementById('deleteForm').action = btn.dataset.roleAction;
                const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            });
        });
    </script>
@endpush
