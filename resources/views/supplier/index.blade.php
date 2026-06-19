@extends('layouts.dashboard',
[
    'title' => 'Supplier',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Supplier</li>'
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
                    <h5 class="mb-1">Daftar Supplier</h5>
                    <small class="text-muted">Data supplier</small>
                </div>

                <div>
                    <a href="{{ route('supplier.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Tambah Supplier
                    </a>
                </div>

            </div>

            <div class="card-body">

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
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

                    <table id="suppliers-table" class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th>ID Supplier</th>
                                <th>Nama Supplier</th>
                                <th>Alamat</th>
                                <th>No Telepon</th>
                                <th>Email</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($suppliers as $supplier)
                                <tr>
                                    <td>{{ $supplier->id_supplier }}</td>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->alamat ?? '-' }}</td>
                                    <td>{{ $supplier->no_tlp ?? '-' }}</td>
                                    <td>{{ $supplier->email ?? '-' }}</td>

                                    <td class="text-end">
                                        <a href="{{ route('supplier.edit', $supplier->id_supplier) }}"
                                           class="text-success me-2">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>

                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-supplier"
                                                data-supplier-name="{{ $supplier->name }}"
                                                data-supplier-action="{{ route('supplier.destroy', $supplier->id_supplier) }}">
                                            <i class="feather icon-trash-2 f-18"></i>
                                        </button>
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    Hapus Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Apakah Anda yakin ingin menghapus supplier
                    <strong id="deleteSupplierName"></strong> ?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>

                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <i class="feather icon-trash-2 me-1"></i>
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
    $('#suppliers-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data supplier.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [5]
            }
        ]
    });

    $(document).on('click', '.btn-delete-supplier', function () {
        const btn = this;

        document.getElementById('deleteSupplierName').textContent =
            btn.dataset.supplierName;

        document.getElementById('deleteForm').action =
            btn.dataset.supplierAction;

        const modal = new bootstrap.Modal(
            document.getElementById('deleteModal')
        );

        modal.show();
    });
});
</script>

@endpush