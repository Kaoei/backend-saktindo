@extends('layouts.dashboard',
[
    'title' => 'Daftar Barang Keluar',
    'pageTitle' => 'Daftar Barang Keluar',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Daftar Barang Keluar</li>'
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
                    <h5 class="mb-1">Daftar Barang Keluar</h5>
                    <small class="text-muted">History pengeluaran barang dari gudang</small>
                </div>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="out-bound-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No Outbound</th>
                                <th>Task Gudang</th>
                                <th>Barang</th>
                                <th>Rack</th>
                                <th>Qty Keluar</th>
                                <th>Tanggal Keluar</th>
                                <th>Delivery</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($outBounds as $outBound)
                                <tr>
                                    <td>{{ $outBound->id }}</td>

                                    <td>{{ $outBound->warehouseTask->id ?? '-' }}</td>

                                    <td>
                                        {{ $outBound->gudangProduct->supplierProduct->item_name ?? '-' }}
                                        <br>
                                        <small class="text-muted">
                                            SKU: {{ $outBound->gudangProduct->supplierProduct->sku ?? '-' }}
                                        </small>
                                    </td>

                                    <td>{{ $outBound->gudangProduct->rack->rak_kode ?? '-' }}</td>

                                    <td>{{ number_format($outBound->qty) }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($outBound->outbound_date)->format('d M Y') }}
                                    </td>

                                    <td>
                                        @if ($outBound->delivery_type == 'full')
                                            <span class="badge bg-success">Full</span>
                                        @elseif ($outBound->delivery_type == 'partial')
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $outBound->delivery_type }}</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($outBound->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif ($outBound->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $outBound->status }}</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('outbound.print', $outBound->id) }}"
                                           class="text-primary me-2"
                                           title="Print">
                                            <i class="feather icon-printer f-18"></i>
                                        </a>

                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-outbound"
                                                data-outbound-name="{{ $outBound->id }}"
                                                data-outbound-action="{{ route('outbound.destroy', $outBound->id) }}"
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

<div class="modal fade" id="deleteOutboundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Hapus Data Barang Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data outbound
                <strong id="deleteOutboundName"></strong> ?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>

                <form id="deleteOutboundForm" method="POST">
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
    $('#out-bound-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data barang keluar.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [8]
            }
        ]
    });

    $(document).on('click', '.btn-delete-outbound', function () {
        document.getElementById('deleteOutboundName').textContent =
            this.dataset.outboundName;

        document.getElementById('deleteOutboundForm').action =
            this.dataset.outboundAction;

        const modal = new bootstrap.Modal(document.getElementById('deleteOutboundModal'));
        modal.show();
    });
});
</script>

@endpush