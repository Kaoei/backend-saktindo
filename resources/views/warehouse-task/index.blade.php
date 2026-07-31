@extends('layouts.dashboard',
[
    'title' => 'Warehouse Task',
    'pageTitle' => 'Warehouse Task',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Warehouse Task</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row mt-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Task</div>
                <h3 class="mb-0">{{ $totalTask }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Waiting</div>
                <h3 class="mb-0">{{ $waitingTask ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Process</div>
                <h3 class="mb-0">{{ $processTask ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Completed</div>
                <h3 class="mb-0">{{ $completedTask ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

@php
    $isSuperAdmin = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
@endphp
<div class="row">
    <div class="col-12">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-semibold">Daftar Warehouse Task</h5>
                    <small class="text-muted">Data task gudang dari sales order & penandaan pemeriksaan admin</small>
                </div>

                @if($isSuperAdmin)
                    <a href="{{ route('warehouse-task.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <span class="material-icons-two-tone text-white me-1">add_circle</span>
                        Tambah Task
                    </a>
                @endif
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
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
                    <table id="warehouse-task-table" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Task</th>
                                <th>Sales Order</th>
                                <th>Invoice</th>
                                <th>PIC Gudang</th>
                                <th>Status Task</th>
                                <th>Pemeriksaan Super Admin</th>
                                <th class="text-end" style="min-width: 250px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($warehouseTasks as $task)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-primary">{{ $task->id }}</span>
                                    </td>

                                    <td>{{ $task->sales_order_id }}</td>

                                    <td>
                                        <code>{{ $task->invoice->id ?? '-' }}</code>
                                        <br>
                                        <small class="text-muted">
                                            {{ $task->invoice->invoice_number ?? '-' }}
                                        </small>
                                    </td>

                                    <td>{{ $task->assigned_to ?? '-' }}</td>

                                    <td>
                                        @if ($task->status == 'waiting')
                                            <span class="badge bg-warning text-dark">Waiting</span>
                                        @elseif ($task->status == 'process')
                                            <span class="badge bg-info">Process</span>
                                        @elseif ($task->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $task->status }}</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($task->is_checked_by_admin)
                                            <span class="badge bg-success" title="{{ $task->checked_by_admin_at ? 'Diperiksa ' . $task->checked_by_admin_at->format('d/m/Y H:i') : '' }}">
                                                🟢 Sudah Diperiksa
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border">
                                                ⚪ Belum Diperiksa
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">

                                            <!-- Tombol Cetak (Semua User) -->
                                            <a href="{{ route('warehouse-task.print', $task->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Cetak Checklist">
                                                <i class="feather icon-printer me-1"></i>
                                                Cetak
                                            </a>

                                            <!-- Tombol Process / Barang Keluar (Semua User) -->
                                            @if ($task->status == 'waiting')
                                                <form action="{{ route('warehouse-task.process', $task->id) }}"
                                                      method="POST"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-info"
                                                            title="Ubah status menjadi process">
                                                        <i class="feather icon-play-circle me-1"></i>
                                                        Process
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($task->status == 'process')
                                                <a href="{{ route('outbound.create', $task->id) }}"
                                                   class="btn btn-sm btn-success"
                                                   title="Buat Barang Keluar">
                                                    <i class="feather icon-package me-1"></i>
                                                    Barang Keluar
                                                </a>
                                            @endif

                                            <!-- Penanda Khusus & Hak Akses Super Admin -->
                                            @if($isSuperAdmin)
                                                <form action="{{ route('warehouse-task.toggle-check', $task->id) }}"
                                                      method="POST"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="btn btn-sm {{ $task->is_checked_by_admin ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                                            title="{{ $task->is_checked_by_admin ? 'Batal tandai periksa' : 'Tandai sudah diperiksa admin' }}">
                                                        <i class="feather {{ $task->is_checked_by_admin ? 'icon-x-circle' : 'icon-check-square' }} me-1"></i>
                                                        {{ $task->is_checked_by_admin ? 'Batal Periksa' : 'Tandai Diperiksa' }}
                                                    </button>
                                                </form>

                                                <a href="{{ route('warehouse-task.edit', $task->id) }}"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="Edit Task">
                                                    <i class="feather icon-edit"></i>
                                                </a>

                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-delete-task"
                                                        data-task-name="{{ $task->id }}"
                                                        data-task-action="{{ route('warehouse-task.destroy', $task->id) }}"
                                                        title="Hapus Task">
                                                    <i class="feather icon-trash-2"></i>
                                                </button>
                                            @endif

                                        </div>
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

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Hapus Warehouse Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Apakah Anda yakin ingin menghapus task
                <strong id="deleteTaskName"></strong>?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>

                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        Hapus
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
    $('#warehouse-task-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data warehouse task.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [6]
            }
        ]
    });

    $(document).on('click', '.btn-delete-task', function () {
        document.getElementById('deleteTaskName').textContent =
            this.dataset.taskName;

        document.getElementById('deleteForm').action =
            this.dataset.taskAction;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });
});
</script>

@endpush