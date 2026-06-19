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

<div class="row">
    <div class="col-12">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Daftar Warehouse Task</h5>
                    <small class="text-muted">Data task gudang dari sales order</small>
                </div>

                <a href="{{ route('warehouse-task.create') }}" class="btn btn-primary btn-sm">
                    <i class="material-icons-two-tone text-white">add_circle</i>
                    Tambah Task
                </a>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="warehouse-task-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID Task</th>
                                <th>Sales Order</th>
                                <th>Invoice</th>
                                <th>PIC Gudang</th>
                                <th>Status</th>
                                <th>Note</th>
                                <th class="text-end" style="width: 320px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($warehouseTasks as $task)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $task->id }}</span>
                                    </td>

                                    <td>{{ $task->sales_order_id }}</td>

                                    <td>
                                        {{ $task->invoice->id ?? '-' }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $task->invoice->invoice_number ?? '-' }}
                                        </small>
                                    </td>

                                    <td>{{ $task->assigned_to ?? '-' }}</td>

                                    <td>
                                        @if ($task->status == 'waiting')
                                            <span class="badge bg-warning">Waiting</span>
                                        @elseif ($task->status == 'process')
                                            <span class="badge bg-info">Process</span>
                                        @elseif ($task->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $task->status }}</span>
                                        @endif
                                    </td>

                                    <td>{{ $task->note ?? '-' }}</td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">

                                            <a href="{{ route('warehouse-task.edit', $task->id) }}"
                                               class="btn btn-sm btn-outline-success"
                                               title="Edit Task">
                                                <i class="feather icon-edit"></i>
                                                Edit
                                            </a>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-delete-task"
                                                    data-taskame="{{ $task->id }}"
                                                    data-task-action="{{ route('warehouse-task.destroy', $task->id) }}"
                                                    title="Hapus Task">
                                                <i class="feather icon-trash-2"></i>
                                                Hapus
                                            </button>
                                            
                                            @if ($task->status == 'waiting')
                                                <form action="{{ route('warehouse-task.process', $task->id) }}"
                                                      method="POST"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-info"
                                                            title="Ubah status menjadi process">
                                                        <i class="feather icon-play-circle"></i>
                                                        Process
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($task->status == 'process')
                                                <a href="{{ route('outbound.create', $task->id) }}"
                                                   class="btn btn-sm btn-success"
                                                   title="Buat Barang Keluar">
                                                    <i class="feather icon-package"></i>
                                                    Barang Keluar
                                                </a>
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