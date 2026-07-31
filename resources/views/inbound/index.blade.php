@extends('layouts.dashboard',
[
    'title' => 'Barang Masuk',
    'pageTitle' => 'Barang Masuk',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Barang Masuk</li>'
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
                    <h5 class="mb-1">Daftar Barang Masuk</h5>
                    <small class="text-muted">Data inbound dari supplier</small>
                </div>

                <a href="{{ route('inbound.create') }}" class="btn btn-primary btn-sm">
                    <i class="material-icons-two-tone text-white">add_circle</i>
                    Tambah Barang Masuk
                </a>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <div class="row mb-3">

                    <div class="col-md-3">
                        <label class="form-label">Filter Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Stored">Stored</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Filter Supplier</label>
                        <select id="filterSupplier" class="form-select">
                            <option value="">Semua Supplier</option>

                            @foreach($inbounds->pluck('supplier.name')->filter()->unique() as $supplier)
                                <option value="{{ $supplier }}">
                                    {{ $supplier }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Masuk</label>
                        <input type="date"
                            id="filterDate"
                            class="form-control">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button"
                                id="resetFilter"
                                class="btn btn-secondary w-100">
                            Reset Filter
                        </button>
                    </div>

                </div>
                <div class="table-responsive">
                    <table id="inbound-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID Inbound</th>
                                <th>Supplier</th>
                                <th>Barang</th>
                                <th>SKU</th>
                                <th>Qty Diterima</th>
                                <th>Tanggal Masuk</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($inbounds as $inbound)
                                <tr>
                                    <td>{{ $inbound->id }}</td>
                                    <td>{{ $inbound->supplier->name ?? '-' }}</td>
                                    <td>{{ $inbound->supplierProduct->item_name ?? '-' }}</td>
                                    <td>{{ $inbound->supplierProduct->sku ?? '-' }}</td>
                                    <td>{{ $inbound->qty_received }}</td>
                                    <td>{{ $inbound->received_date }}</td>
                                    <td>
                                        @if ($inbound->status == 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif ($inbound->status == 'stored')
                                            <span class="badge bg-success">Stored</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $inbound->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($inbound->status == 'pending')
                                           <a href="{{ route('gudang-product.create', ['inbound_id' => $inbound->id]) }}"
                                                class="text-primary me-2"
                                                title="Process">
                                                    <i class="feather icon-play-circle f-18"></i>
                                            </a>
                                            <a href="{{ route('inbound.edit', $inbound->id) }}"
                                            class="text-success me-2"
                                            title="Edit">
                                                <i class="feather icon-edit f-18"></i>
                                            </a>
                                            <form action="{{ route('inbound.cancel', $inbound->id) }}"
                                                method="POST"
                                                class="d-inline me-2">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                        class="btn p-0 border-0 bg-transparent text-warning"
                                                        title="Cancel"
                                                        onclick="return confirm('Batalkan inbound ini?')">
                                                    <i class="feather icon-x-circle f-18"></i>
                                                </button>
                                            </form>

                                        @endif
                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-inbound"
                                                data-inbound-name="{{ $inbound->id }}"
                                                data-inbound-action="{{ route('inbound.destroy', $inbound->id) }}"
                                                title="Hapus">
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

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Hapus Barang Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data
                <strong id="deleteInboundName"></strong> ?
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

    const table = $('#inbound-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data barang masuk.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [7]
            }
        ]
    });

    // Filter Status
    $('#filterStatus').on('change', function () {
        table.column(6).search(this.value).draw();
    });

    // Filter Supplier
    $('#filterSupplier').on('change', function () {
        table.column(1).search(this.value).draw();
    });

    // Filter Tanggal
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {

        const filterDate = $('#filterDate').val();
        const tableDate = data[5];

        if (!filterDate) {
            return true;
        }

        return tableDate === filterDate;
    });

    $('#filterDate').on('change', function () {
        table.draw();
    });

    // Reset Filter
    $('#resetFilter').on('click', function () {

        $('#filterStatus').val('');
        $('#filterSupplier').val('');
        $('#filterDate').val('');

        table.search('').columns().search('').draw();
    });

    // Modal delete
    $(document).on('click', '.btn-delete-inbound', function () {

        document.getElementById('deleteInboundName').textContent =
            this.dataset.inboundName;

        document.getElementById('deleteForm').action =
            this.dataset.inboundAction;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });

});
</script>

@endpush