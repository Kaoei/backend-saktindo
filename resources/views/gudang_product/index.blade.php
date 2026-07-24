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

<div class="row">
    <div class="col-12">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Daftar Produk Gudang</h5>
                    <small class="text-muted">Data barang yang sudah ditempatkan ke rak</small>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('gudang-product.importPage') }}" class="btn btn-secondary btn-sm">
                        <i class="feather icon-upload"></i> Import Excel
                    </a>
                    <a href="{{ route('gudang-product.export') }}" class="btn btn-info btn-sm text-white">
                        <i class="feather icon-download"></i> Export Excel
                    </a>
                    <a href="{{ route('gudang-product.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Simpan Barang ke Rak
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

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="mb-4">
                    <form method="GET" action="{{ route('gudang-product.index') }}" class="d-flex align-items-center gap-2">
                        <span class="fw-bold">Filter Gudang:</span>
                        <a href="{{ route('gudang-product.index') }}" class="btn btn-sm {{ !request()->filled('gudang') ? 'btn-primary' : 'btn-outline-primary' }}">Semua</a>
                        <a href="{{ route('gudang-product.index', ['gudang' => 'js']) }}" class="btn btn-sm {{ request('gudang') === 'js' ? 'btn-primary' : 'btn-outline-primary' }}">Gudang JS</a>
                        <a href="{{ route('gudang-product.index', ['gudang' => 'sjb']) }}" class="btn btn-sm {{ request('gudang') === 'sjb' ? 'btn-primary' : 'btn-outline-primary' }}">Gudang SJB</a>
                    </form>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error_list'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6 class="alert-heading font-weight-bold mb-2">Detail Kesalahan Import:</h6>
                        <ul class="mb-0 ps-3" style="max-height: 200px; overflow-y: auto;">
                            @foreach (session('error_list') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
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
                                <th>Harga</th>
                                <th>Discount</th>
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
                                    <td>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $product->discount, 0, ',', '.') }}</td>
                                    <td>{{ $product->rack->rak_kode ?? '-' }}</td>
                                    <td>{{ $product->rack->location ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('gudang-product.edit', $product->id) }}" class="btn p-0 border-0 bg-transparent text-primary me-2">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>
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
                targets: [10]
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