@extends('layouts.dashboard', [
    'title' => 'User Management',
    'pageTitle' => 'User Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">User Management</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Users</h5>
                @if(auth()->user()?->hasPermission('users.create'))
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="material-icons-two-tone text-white">person_add</i>
                        Create User
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="users-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Group</th>
                                <th>Created</th>
                                <th class="text-end" style="width: 110px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge bg-light-secondary font-monospace">{{ $user->id }}</span>
                                    </td>
                                    <td class="align-middle fw-semibold">{{ $user->name }}</td>
                                    <td class="align-middle">{{ $user->email }}</td>
                                    <td class="align-middle">
                                        @php
                                            $badgeClass = match ($user->role) {
                                                \App\Models\User::ROLE_SUPER_ADMIN => 'bg-light-danger',
                                                \App\Models\User::ROLE_TEKNISI    => 'bg-light-warning',
                                                \App\Models\User::ROLE_SALES      => 'bg-light-success',
                                                \App\Models\User::ROLE_FINANCE    => 'bg-light-info',
                                                default                           => 'bg-light-primary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $user->role_label }}</span>
                                    </td>
                                    <td class="align-middle">
                                        @if($user->group)
                                            <span class="badge bg-light-secondary">{{ $user->group }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $user->created_at?->format('Y-m-d') }}</td>
                                    <td class="align-middle text-end">
                                        @if(auth()->user()?->hasPermission('users.edit'))
                                            <a href="{{ route('users.edit', $user) }}" class="text-success" title="Edit">
                                                <i class="feather icon-edit f-16 text-success"></i>
                                            </a>
                                            <a href="{{ route('users.reset-password', $user) }}" class="text-warning ms-2" title="Reset Password">
                                                <i class="feather icon-lock f-16 text-warning"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('users.delete'))
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                                  onsubmit="return confirm('Delete this user?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn p-0 border-0 bg-transparent" title="Delete">
                                                    <i class="feather icon-trash-2 ml-3 f-16 text-danger"></i>
                                                </button>
                                            </form>
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
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#users-table').DataTable({
                pageLength: 10,
                order: [[5, 'desc']],
                language: { emptyTable: 'Belum ada user.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [6] }
                ]
            });
        });
    </script>
@endpush
