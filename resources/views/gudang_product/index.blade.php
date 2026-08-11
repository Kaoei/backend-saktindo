@extends('layouts.dashboard',
[
    'title' => 'Produk Gudang',
    'pageTitle' => 'Produk Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Produk Gudang</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row mb-4">

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Produk</p>
                        <h3 class="mb-0">{{ $totalProduk ?? 0 }}</h3>
                    </div>
                    <i class="feather icon-package text-primary" style="font-size:32px"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Qty</p>
                        <h3 class="mb-0">{{ $totalQty ?? 0 }}</h3>
                    </div>
                    <i class="feather icon-boxes text-success" style="font-size:32px"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <p class="text-muted mb-1">Gudang JS</p>
                        <h3 class="mb-0">{{ $totalJS ?? 0 }}</h3>
                    </div>
                    <i class="feather icon-building text-warning" style="font-size:32px"></i>
                </div>

                <hr>

                <small class="text-muted fw-bold">Top 3 Rak</small>

                @forelse($topRakJS ?? [] as $rak)
                    <div class="d-flex justify-content-between mt-2">
                        <span>{{ $rak->rack_id }}</span>
                        <span class="fw-bold">{{ $rak->total_qty }}</span>
                    </div>
                @empty
                    <div class="text-muted mt-2">Belum ada data</div>
                @endforelse

            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <p class="text-muted mb-1">Gudang SJB</p>
                        <h3 class="mb-0">{{ $totalSJB ?? 0 }}</h3>
                    </div>
                    <i class="feather icon-home text-danger" style="font-size:32px"></i>
                </div>

                <hr>

                <small class="text-muted fw-bold">Top 3 Rak</small>

                @forelse($topRakSJB ?? [] as $rak)
                    <div class="d-flex justify-content-between mt-2">
                        <span>{{ $rak->rack_id }}</span>
                        <span class="fw-bold">{{ $rak->total_qty }}</span>
                    </div>
                @empty
                    <div class="text-muted mt-2">Belum ada data</div>
                @endforelse

            </div>
        </div>
    </div>

</div>
<div class="row">
    <div class="col-12">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Daftar Produk Gudang</h5>
                    <small class="text-muted">Data barang yang sudah ditempatkan ke rak</small>
                </div>

                <a href="{{ route('gudang-product.create') }}" class="btn btn-primary btn-sm">
                    <i class="material-icons-two-tone text-white">add_circle</i>
                    Simpan Barang ke Rak
                </a>
            </div>

            <div class="card-body">

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="gudang-product-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Barang</th>
                                <th>SKU</th>
                                <th>Brand</th>
                                <th>Qty</th>
                                <th>Gudang</th>
                                <th>Rak</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>{{ $product->supplierProduct->item_name ?? '-' }}</td>
                                    <td>{{ $product->supplierProduct->sku ?? '-' }}</td>
                                    <td>{{ $product->supplierProduct->brand ?? '-' }}</td>
                                    <td>{{ $product->qty }}</td>
                                    <td>{{ $product->gudang_type }}</td>
                                    <td>{{ $product->rack->rak_kode ?? '-' }}</td>
                                    <td>{{ $product->rack->location ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-product"
                                                data-product-name="{{ $product->id }}"
                                                data-product-action="{{ route('gudang-product.destroy', $product->id) }}">
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
                <h5 class="modal-title">Hapus Produk Gudang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data
                <strong id="deleteProductName"></strong> ?
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
    $('#gudang-product-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada produk gudang.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [9]
            }
        ]
    });

    $(document).on('click', '.btn-delete-product', function () {
        document.getElementById('deleteProductName').textContent =
            this.dataset.productName;

        document.getElementById('deleteForm').action =
            this.dataset.productAction;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });
});
</script>

@endpush