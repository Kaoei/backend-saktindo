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

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-semibold">Daftar Barang Masuk</h5>
                    <small class="text-muted">Data inbound & penerimaan barang dari supplier</small>
                </div>

                <a href="{{ route('inbound.create') }}" class="btn btn-primary btn-sm d-flex align-items-center">
                    <span class="material-icons-two-tone text-white me-1">add_circle</span>
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

                <div class="table-responsive">
                    <table id="inbound-table" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Inbound</th>
                                <th>Supplier</th>
                                <th>No. PO</th>
                                <th>Barang</th>
                                <th>SKU</th>
                                <th class="text-center">Kondisi Qty (Baik / Rusak / Kurang)</th>
                                <th>HPP Unit</th>
                                <th>Tgl Masuk</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($inbounds as $inbound)
                                <tr>
                                    <td class="fw-semibold text-primary">{{ $inbound->id }}</td>
                                    <td>{{ $inbound->supplier->name ?? '-' }}</td>
                                    <td>
                                        @if($inbound->supplierPo)
                                            <span class="badge bg-light text-dark border">{{ $inbound->supplierPo->po_number }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $inbound->supplierProduct->item_name ?? '-' }}</td>
                                    <td><code>{{ $inbound->supplierProduct->sku ?? '-' }}</code></td>
                                    <td class="text-center">
                                        <span class="badge bg-success" title="Baik / Diterima">{{ $inbound->qty_received }} Baik</span>
                                        @if($inbound->qty_damaged > 0)
                                            <span class="badge bg-warning text-dark" title="Rusak">{{ $inbound->qty_damaged }} Rusak</span>
                                        @endif
                                        @if($inbound->qty_missing > 0)
                                            <span class="badge bg-danger" title="Tidak Ada / Kurang">{{ $inbound->qty_missing }} Kurang</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-success">Rp {{ number_format($inbound->hpp, 0, ',', '.') }}</td>
                                    <td>{{ date('d/m/Y', strtotime($inbound->received_date)) }}</td>
                                    <td>
                                        @if ($inbound->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif ($inbound->status == 'stored')
                                            <span class="badge bg-success">Stored</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $inbound->status }}</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('inbound.edit', $inbound->id) }}"
                                           class="text-success me-2" title="Edit">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>

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
    $('#inbound-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data barang masuk.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [9]
            }
        ]
    });

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