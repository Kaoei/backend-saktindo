@extends('layouts.dashboard',
[
    'title' => 'Produk Gudang',
    'pageTitle' => 'Produk Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Produk Gudang</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.25rem;
        text-align: right;
    }
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        color: #495057;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        padding: 6px 14px;
        margin-left: 8px;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.2s ease-in-out;
        min-width: 250px;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #4680ff;
        box-shadow: 0 0 0 0.2rem rgba(70, 128, 255, 0.15);
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1.25rem;
        font-weight: 500;
        color: #495057;
    }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        padding: 5px 10px;
        margin: 0 6px;
        outline: none;
    }
    .dataTables_wrapper .dataTables_info {
        padding-top: 1rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
    }
</style>
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
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <div class="mb-2 mb-md-0">
                    <h5 class="mb-1">Daftar Produk Gudang</h5>
                    <small class="text-muted">Data barang yang sudah ditempatkan ke rak</small>
                </div>

                <div class="d-inline-flex align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm me-2 d-inline-flex align-items-center" data-toggle="modal" data-target="#manualInputModal" data-bs-toggle="modal" data-bs-target="#manualInputModal" style="width: auto !important; flex: none !important;">
                        <i class="feather icon-plus-circle me-1"></i>
                        Input Stock
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-2 d-inline-flex align-items-center" data-toggle="modal" data-target="#importModal" data-bs-toggle="modal" data-bs-target="#importModal" style="width: auto !important; flex: none !important;">
                        <i class="feather icon-upload me-1"></i>
                        Import Stok Excel
                    </button>
                    <a href="{{ route('gudang-product.create') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" style="width: auto !important; flex: none !important;">
                        <i class="feather icon-download me-1"></i>
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
                                @php
                                    $otherGudangProducts = $product->supplierProduct?->gudangProducts?->where('id', '!=', $product->id)->where('qty', '>', 0);
                                    $otherCount = $otherGudangProducts ? $otherGudangProducts->count() : 0;
                                    $otherDetails = [];
                                    $totalProductStock = $product->qty;
                                    if ($otherCount > 0) {
                                        foreach ($otherGudangProducts as $ogp) {
                                            $totalProductStock += $ogp->qty;
                                            $otherDetails[] = ($ogp->rack->rak_kode ?? $ogp->rack_id ?? '-') . ' (' . $ogp->qty . ' pcs)';
                                        }
                                    }
                                    $otherDetailsText = count($otherDetails) > 0 ? implode(', ', $otherDetails) : '';
                                @endphp
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $product->supplierProduct->item_name ?? '-' }}</div>
                                        @if($otherCount > 0)
                                            <small class="text-primary d-block" title="{{ $otherDetailsText }}">
                                                <i class="feather icon-layers me-1"></i>Juga di: {{ $otherDetailsText }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $product->supplierProduct->sku ?? '-' }}
                                        @if($otherCount > 0)
                                            <span class="badge bg-light-primary text-primary border ms-1" title="Total semua rak: {{ $totalProductStock }} pcs">
                                                Total: {{ $totalProductStock }} pcs
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $product->supplierProduct->brand ?? '-' }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $product->qty }}</span>
                                    </td>
                                    <td>{{ $product->gudang_type }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $product->rack->rak_kode ?? '-' }}</span>
                                    </td>
                                    <td>{{ $product->rack->location ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-primary me-2 btn-split-rack"
                                                data-id="{{ $product->id }}"
                                                data-item-name="{{ $product->supplierProduct->item_name ?? '-' }}"
                                                data-sku="{{ $product->supplierProduct->sku ?? '-' }}"
                                                data-current-rack="{{ $product->rack->rak_kode ?? $product->rack_id ?? '-' }}"
                                                data-current-gudang="{{ $product->gudang_type }}"
                                                data-current-qty="{{ $product->qty }}"
                                                title="Bagi / Pindah Stok ke Rak Lain">
                                            <i class="feather icon-shuffle f-18"></i>
                                        </button>
                                        <a href="{{ route('gudang-product.edit', $product->id) }}"
                                           class="text-success me-2"
                                           title="Edit Stok & Rak">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>
                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-product"
                                                data-product-name="{{ $product->id }}"
                                                data-product-action="{{ route('gudang-product.destroy', $product->id) }}"
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
            <form method="POST" action="{{ route('gudang-product.storeManual') }}" id="form-manual-stock">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <!-- Opsi Pilih Produk Yang Sudah Ada -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-primary mb-1">
                                <i class="feather icon-search me-1"></i>Pilih Produk Yang Sudah Ada (Opsional)
                            </label>
                            <select id="select-existing-product" class="form-select border-primary shadow-sm">
                                <option value="">-- Ketik Produk Baru Manual --</option>
                                @foreach($supplierProducts ?? [] as $sp)
                                    <option value="{{ $sp->id }}"
                                        data-name="{{ $sp->item_name }}"
                                        data-sku="{{ $sp->sku }}"
                                        data-brand="{{ $sp->brand }}"
                                        data-category="{{ $sp->category }}"
                                        data-subcategory="{{ $sp->sub_category }}"
                                        data-price="{{ $sp->last_purchase_price }}">
                                        {{ $sp->item_name }} (SKU: {{ $sp->sku }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="supplier_product_id" id="hidden_supplier_product_id">
                            <small class="text-muted">Pilih jika ingin menambahkan stok di rak baru untuk produk yang sudah terdaftar.</small>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="manual_item_name" class="form-control" placeholder="Contoh: Lampu LED Bulb 10W Philips" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SKU / Serial Number</label>
                            <input type="text" name="sku" id="manual_sku" class="form-control" placeholder="Otomatis jika kosong">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand / Merek</label>
                            <input type="text" name="brand" id="manual_brand" class="form-control" list="brandOptions" placeholder="Pilih atau ketik baru">
                            <datalist id="brandOptions">
                                @foreach($brands ?? [] as $b)
                                    <option value="{{ $b->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori Utama</label>
                            <input type="text" name="category" id="manual_category" class="form-control" list="categoryOptions" placeholder="Pilih atau ketik baru">
                            <datalist id="categoryOptions">
                                @foreach($categories ?? [] as $c)
                                    <option value="{{ $c->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sub Kategori</label>
                            <input type="text" name="sub_category" id="manual_sub_category" class="form-control" placeholder="Contoh: Bulb / Panel / Downlight">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Harga Per Unit (Rp)</label>
                            <input type="number" name="price" id="manual_price" class="form-control" min="0" step="100" placeholder="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Penyimpanan</label>
                            <select name="status" class="form-select">
                                <option value="stored" selected>Stored (Tersimpan)</option>
                                <option value="pending">Pending</option>
                                <option value="damaged">Damaged (Rusak)</option>
                            </select>
                        </div>

                        <!-- Multi-rack allocation table -->
                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <div>
                                    <label class="form-label fw-bold mb-0 text-dark">
                                        <i class="feather icon-layers text-primary me-1"></i>
                                        Penempatan Rak & Kuantitas
                                    </label>
                                    <small class="text-muted d-block">Bisa menempatkan stok ke satu atau beberapa rak sekaligus</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-manual-rack">
                                    <i class="feather icon-plus me-1"></i> Tambah Rak Lain
                                </button>
                            </div>

                            <div class="table-responsive border rounded bg-white p-2">
                                <table class="table table-sm align-middle mb-0" id="manual-rack-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Gudang <span class="text-danger">*</span></th>
                                            <th style="width: 45%;">Rak Penyimpanan <span class="text-danger">*</span></th>
                                            <th style="width: 22%;">Qty <span class="text-danger">*</span></th>
                                            <th style="width: 8%;" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="manual-rack-body">
                                        <!-- dynamic rows -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light fw-bold">
                                            <td colspan="2" class="text-end">Total Stok Masuk:</td>
                                            <td colspan="2"><span id="manual-total-qty-badge" class="badge bg-primary fs-6">0 pcs</span></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="feather icon-save me-1"></i> Simpan Stok Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Split / Pindah Rak -->
<div class="modal fade" id="splitRackModal" tabindex="-1" aria-labelledby="splitRackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="splitRackModalLabel">
                    <i class="feather icon-shuffle text-primary me-2"></i>Bagi / Pindah Stok ke Rak Lain
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('gudang-product.splitRack') }}" id="form-split-rack">
                @csrf
                <input type="hidden" name="source_id" id="split_source_id">

                <div class="modal-body py-4">
                    <div class="card bg-light border-0 p-3 mb-3">
                        <strong id="split_item_name" class="text-dark fs-6">-</strong>
                        <div class="small text-muted mt-1">
                            <span id="split_sku" class="badge bg-secondary me-2">-</span>
                            Rak Asal: <strong id="split_current_rack" class="text-dark">-</strong>
                            (<span id="split_current_gudang">-</span>) &bull;
                            Stok Saat Ini: <span id="split_current_qty" class="badge bg-primary">0 pcs</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Jumlah Qty yang Dipindahkan <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" name="qty" id="split_qty" class="form-control" min="1" required placeholder="Contoh: 10">
                            <span class="input-group-text">pcs</span>
                        </div>
                        <small class="text-muted">Kuantitas ini akan dikurangi dari rak asal dan ditambahkan ke rak tujuan.</small>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-semibold">Gudang Tujuan <span class="text-danger">*</span></label>
                            <select name="target_gudang_type" id="split_target_gudang" class="form-select" required>
                                <option value="JS">Gudang JS</option>
                                <option value="SJB">Gudang SJB</option>
                            </select>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-semibold">Rak Tujuan <span class="text-danger">*</span></label>
                            <select name="target_rack_id" id="split_target_rack" class="form-select" required>
                                <option value="">-- Pilih Rak Tujuan --</option>
                                @foreach($racks ?? [] as $rack)
                                    <option value="{{ $rack->rak_kode }}" data-gudang="{{ strtoupper($rack->gudang ?? '') }}">
                                        {{ $rack->rak_kode }} - {{ $rack->location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="feather icon-check me-1"></i> Proses Pindah / Bagi Rak
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
    const racksData = @json($racks ?? []);

    $('#gudang-product-table').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        language: {
            search: "Cari Produk:",
            searchPlaceholder: "Ketik nama barang, SKU, brand, rak...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data produk gudang tidak ditemukan.",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ produk",
            infoEmpty: "Menampilkan 0 - 0 dari 0 produk",
            infoFiltered: "(disaring dari _MAX_ total produk)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "▶",
                previous: "◀"
            },
            emptyTable: 'Belum ada produk gudang.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [9]
            }
        ]
    });

    // Inisialisasi tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    // Modal Delete
    $(document).on('click', '.btn-delete-product', function () {
        document.getElementById('deleteProductName').textContent = this.dataset.productName;
        document.getElementById('deleteForm').action = this.dataset.productAction;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        } else {
            $('#deleteModal').modal('show');
        }
    });

    // Modal Split / Pindah Rak
    $(document).on('click', '.btn-split-rack', function () {
        const id = $(this).data('id');
        const itemName = $(this).data('item-name');
        const sku = $(this).data('sku');
        const currentRack = $(this).data('current-rack');
        const currentGudang = $(this).data('current-gudang') || 'JS';
        const currentQty = parseInt($(this).data('current-qty')) || 0;

        $('#split_source_id').val(id);
        $('#split_item_name').text(itemName);
        $('#split_sku').text('SKU: ' + sku);
        $('#split_current_rack').text(currentRack);
        $('#split_current_gudang').text('Gudang ' + currentGudang);
        $('#split_current_qty').text(currentQty + ' pcs');
        $('#split_qty').attr('max', currentQty).val('');
        $('#split_target_rack').val('');
        $('#split_target_gudang').val(currentGudang);

        // Filter out current rack from target selection
        $('#split_target_rack option').each(function() {
            if ($(this).val() === currentRack) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false);
            }
        });

        const el = document.getElementById('splitRackModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            modal.show();
        } else {
            $('#splitRackModal').modal('show');
        }
    });

    // Match gudang on target rack change
    $('#split_target_rack').on('change', function() {
        const selectedGudang = $(this).find('option:selected').attr('data-gudang');
        if (selectedGudang && (selectedGudang === 'JS' || selectedGudang === 'SJB')) {
            $('#split_target_gudang').val(selectedGudang);
        }
    });

    // Auto-fill when selecting existing product in manual input
    $('#select-existing-product').on('change', function() {
        const $opt = $(this).find('option:selected');
        const id = $(this).val();

        if (id) {
            $('#hidden_supplier_product_id').val(id);
            $('#manual_item_name').val($opt.data('name') || '');
            $('#manual_sku').val($opt.data('sku') || '');
            $('#manual_brand').val($opt.data('brand') || '');
            $('#manual_category').val($opt.data('category') || '');
            $('#manual_sub_category').val($opt.data('subcategory') || '');
            $('#manual_price').val($opt.data('price') || 0);
        } else {
            $('#hidden_supplier_product_id').val('');
            $('#manual_item_name').val('');
            $('#manual_sku').val('');
            $('#manual_brand').val('');
            $('#manual_category').val('');
            $('#manual_sub_category').val('');
            $('#manual_price').val('');
        }
    });

    // Multi-rack rows in manual input modal
    let manualRowIndex = 0;
    const $manualRackBody = $('#manual-rack-body');
    const $manualTotalBadge = $('#manual-total-qty-badge');

    function buildManualRackOptions(selectedGudang = '', selectedRack = '') {
        let html = '<option value="">-- Pilih Rak --</option>';
        racksData.forEach(r => {
            const gudangLabel = r.gudang ? r.gudang.toUpperCase() : '';
            const isSelected = (selectedRack && String(r.rak_kode) === String(selectedRack)) ? 'selected' : '';
            const locationText = r.location ? ` - ${r.location}` : '';
            const gudangBadge = gudangLabel ? ` [${gudangLabel}]` : '';
            html += `<option value="${r.rak_kode}" data-gudang="${gudangLabel}" ${isSelected}>${r.rak_kode}${gudangBadge}${locationText}</option>`;
        });
        return html;
    }

    function addManualRackRow(gudangType = 'JS', rackId = '', qty = 1) {
        const index = manualRowIndex++;
        const optionsHtml = buildManualRackOptions(gudangType, rackId);

        const trHtml = `
            <tr class="manual-rack-row" data-index="${index}">
                <td>
                    <select name="allocations[${index}][gudang_type]" class="form-select form-select-sm manual-gudang-select" required>
                        <option value="JS" ${gudangType === 'JS' ? 'selected' : ''}>Gudang JS</option>
                        <option value="SJB" ${gudangType === 'SJB' ? 'selected' : ''}>Gudang SJB</option>
                    </select>
                </td>
                <td>
                    <select name="allocations[${index}][rack_id]" class="form-select form-select-sm manual-rack-select" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" name="allocations[${index}][qty]" class="form-control form-control-sm manual-qty-input text-end" min="1" value="${qty}" required>
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 remove-manual-rack-btn" title="Hapus">
                        <i class="feather icon-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;

        const $tr = $(trHtml);
        $manualRackBody.append($tr);

        $tr.find('.manual-rack-select').on('change', function() {
            const rackGudang = $(this).find('option:selected').attr('data-gudang');
            if (rackGudang && (rackGudang === 'JS' || rackGudang === 'SJB')) {
                $tr.find('.manual-gudang-select').val(rackGudang);
            }
        });

        $tr.find('.manual-qty-input').on('input change', function() {
            recalculateManualTotal();
        });

        $tr.find('.remove-manual-rack-btn').on('click', function() {
            if ($manualRackBody.find('.manual-rack-row').length > 1) {
                $tr.remove();
                recalculateManualTotal();
            } else {
                alert('Minimal harus ada 1 alokasi rak.');
            }
        });

        recalculateManualTotal();
    }

    function recalculateManualTotal() {
        let total = 0;
        $manualRackBody.find('.manual-qty-input').each(function() {
            total += parseInt($(this).val()) || 0;
        });
        $manualTotalBadge.text(total + ' pcs');
    }

    $('#btn-add-manual-rack').on('click', function() {
        addManualRackRow('JS', '', 1);
    });

    // Initialize 1 row in manual input modal on ready
    if ($manualRackBody.children().length === 0) {
        addManualRackRow('JS', '', 1);
    }

    // Modal open handlers
    $(document).on('click', '[data-target="#manualInputModal"], [data-bs-target="#manualInputModal"]', function (e) {
        e.preventDefault();
        const el = document.getElementById('manualInputModal');
        if (el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                modal.show();
            } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                $('#manualInputModal').modal('show');
            }
        }
    });

    $(document).on('click', '[data-target="#importModal"], [data-bs-target="#importModal"]', function (e) {
        e.preventDefault();
        const el = document.getElementById('importModal');
        if (el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                modal.show();
            } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                $('#importModal').modal('show');
            }
        }
    });

    $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"]', function (e) {
        e.preventDefault();
        const modalEl = $(this).closest('.modal');
        if (modalEl.length) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const inst = bootstrap.Modal.getInstance(modalEl[0]);
                if (inst) {
                    inst.hide();
                    return;
                }
            }
            modalEl.modal('hide');
        }
    });
});
</script>

@endpush