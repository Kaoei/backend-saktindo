@extends('layouts.dashboard',
[
    'title' => 'Product',
    'pageTitle' => 'Product',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Product</li>'
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
                    <h5 class="mb-1">Daftar Product</h5>
                    <small class="text-muted">Data master product</small>
                </div>

                <div>
                    <a href="{{ route('product.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Tambah Product
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

                    <table id="products-table" class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th>ID Product</th>
                                <th>SKU</th>
                                <th>Nama Product</th>
                                <th>Deskripsi</th>
                                <th>Stock</th>
                                <th>Tanggal Masuk</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->id_product }}</td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->desc ?? '-' }}</td>
                                    <td>{{ $product->stock }}</td>
                                    <td>{{ $product->Tgl_masuk ?? '-' }}</td>

                                    <td class="text-end">
                                        <a href="{{ route('product.edit', $product->id_product) }}"
                                           class="text-success me-2">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>

                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-product"
                                                data-product-name="{{ $product->name }}"
                                                data-product-action="{{ route('product.destroy', $product->id_product) }}">
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
                    Hapus Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Apakah Anda yakin ingin menghapus product
                    <strong id="deleteProductName"></strong> ?
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
    $('#products-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data product.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [6]
            }
        ]
    });

    $(document).on('click', '.btn-delete-product', function () {
        const btn = this;

        document.getElementById('deleteProductName').textContent =
            btn.dataset.productName;

        document.getElementById('deleteForm').action =
            btn.dataset.productAction;

        const modal = new bootstrap.Modal(
            document.getElementById('deleteModal')
        );

        modal.show();
    });
});
</script>

@endpush