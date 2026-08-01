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

                <div>
                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal" data-target="#manualInputModal" data-bs-toggle="modal" data-bs-target="#manualInputModal">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Input Stock
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-2" data-toggle="modal" data-target="#importModal" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="material-icons-two-tone">publish</i>
                        Import Stok Excel
                    </button>
                    <a href="{{ route('gudang-product.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons-two-tone">view_in_ar</i>
                        Dari Inbound
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
<!-- Modal Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="importModalLabel">Import Stok Barang (Excel / CSV)</h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('gudang-product.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body py-4">
                    <div class="alert alert-info border-0 mb-3 small">
                        <i class="feather icon-info me-1"></i> Format file Excel 100% kompatibel dengan file spreadsheet yang digunakan pada <strong>web-led</strong>. Kolom <em>Nama Barang, Brand, Qty, Harga, Serial Number, Rak Kode, Kategori 1, Kategori 2</em> akan otomatis dikenali.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload File Spreadsheet</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text mt-1 text-muted">Format yang didukung: <strong>.xlsx, .xls, .csv</strong></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="{{ route('gudang-product.download-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="feather icon-download me-1"></i> Download Template
                        </a>
                        <a href="{{ route('gudang-product.importPage') }}" class="small text-decoration-none fw-semibold">
                            Halaman Panduan Impor &rarr;
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="feather icon-upload me-1"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Input Manual -->
<div class="modal fade" id="manualInputModal" tabindex="-1" aria-labelledby="manualInputModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="manualInputModalLabel">Input Stock Barang</h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('gudang-product.storeManual') }}">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="Contoh: Lampu LED Bulb 10W Philips" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SKU / Serial Number</label>
                            <input type="text" name="sku" class="form-control" placeholder="Otomatis jika kosong">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand / Merek</label>
                            <input type="text" name="brand" class="form-control" list="brandOptions" placeholder="Pilih atau ketik baru">
                            <datalist id="brandOptions">
                                @foreach($brands ?? [] as $b)
                                    <option value="{{ $b->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori Utama</label>
                            <input type="text" name="category" class="form-control" list="categoryOptions" placeholder="Pilih atau ketik baru">
                            <datalist id="categoryOptions">
                                @foreach($categories ?? [] as $c)
                                    <option value="{{ $c->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sub Kategori</label>
                            <input type="text" name="sub_category" class="form-control" placeholder="Contoh: Bulb / Panel / Downlight">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jumlah Stok (Qty) <span class="text-danger">*</span></label>
                            <input type="number" name="qty" class="form-control" min="1" value="1" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Harga Per Unit (Rp)</label>
                            <input type="number" name="price" class="form-control" min="0" step="100" placeholder="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status Penyimpanan</label>
                            <select name="status" class="form-select">
                                <option value="stored" selected>Stored (Tersimpan)</option>
                                <option value="pending">Pending</option>
                                <option value="damaged">Damaged (Rusak)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gudang Penyimpanan <span class="text-danger">*</span></label>
                            <select name="gudang_type" class="form-select" required>
                                <option value="">-- Pilih Gudang --</option>
                                <option value="JS" selected>Gudang JS</option>
                                <option value="SJB">Gudang SJB</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rak Penyimpanan <span class="text-danger">*</span></label>
                            <select name="rack_id" class="form-select" required>
                                <option value="">-- Pilih Rak --</option>
                                @foreach($racks ?? [] as $rack)
                                    <option value="{{ $rack->rak_kode }}">
                                        {{ $rack->rak_kode }} - {{ $rack->location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="feather icon-save me-1"></i> Simpan Stok Barang
                    </button>
                </div>
            </form>
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

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        } else {
            $('#deleteModal').modal('show');
        }
    });

    $(document).on('click', '[data-target="#manualInputModal"], [data-bs-target="#manualInputModal"]', function (e) {
        e.preventDefault();
        $('#manualInputModal').modal('show');
    });

    $(document).on('click', '[data-target="#importModal"], [data-bs-target="#importModal"]', function (e) {
        e.preventDefault();
        $('#importModal').modal('show');
    });
});
</script>

@endpush