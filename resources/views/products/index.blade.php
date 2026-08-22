@extends('layouts.dashboard', [
    'title' => 'Master Produk',
    'pageTitle' => 'Master Produk',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Master Produk</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">Master Produk (Katalog Terpadu)</h5>
                    <small class="text-muted">Master data barang terintegrasi langsung dengan Stok Gudang, Sales Order, dan Inbound</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" data-toggle="modal" data-target="#importModal" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="feather icon-upload me-1"></i> Import Excel
                    </button>
                    <a href="{{ route('gudang-product.download-template') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="feather icon-download me-1"></i> Template
                    </a>
                    <a href="{{ route('gudang-product.index') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="feather icon-boxes me-1"></i> Halaman Stok Gudang
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm border-0" role="alert">
                        <i class="feather icon-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm border-0" role="alert">
                        <i class="feather icon-alert-triangle me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif

                <!-- Filter Bar -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <form action="{{ route('products.index') }}" method="GET" class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="feather icon-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari SKU, Nama Barang, Brand, Kategori..." value="{{ $search ?? '' }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <select name="category" class="form-select bg-white">
                                    <option value="">-- Semua Kategori --</option>
                                    @foreach($categoriesList as $cat)
                                        <option value="{{ $cat }}" {{ ($category ?? '') == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select name="brand" class="form-select bg-white">
                                    <option value="">-- Semua Brand --</option>
                                    @foreach($brandsList as $b)
                                        <option value="{{ $b }}" {{ ($brand ?? '') == $b ? 'selected' : '' }}>
                                            {{ $b }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                    <i class="feather icon-filter me-1"></i> Filter
                                </button>
                                @if($search || $category || $brand)
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                                        <i class="feather icon-rotate-ccw"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU / ID</th>
                                <th>Nama Barang</th>
                                <th>Brand</th>
                                <th>Kategori</th>
                                <th>Varian</th>
                                <th>Harga Unit</th>
                                <th>Total Stok Gudang</th>
                                <th>Rak Penyimpanan</th>
                                <th class="text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $totalQty = $product->gudangProducts->sum('qty');
                                    $effectivePrice = (float)$product->last_purchase_price > 0 
                                        ? (float)$product->last_purchase_price 
                                        : (float)($product->gudangProducts->max('price') ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light-secondary font-monospace text-dark">{{ $product->sku ?: $product->id }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $product->item_name }}</div>
                                        @if($product->notes)
                                            <small class="text-muted">{{ $product->notes }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light-primary text-primary">{{ $product->brand ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark small">{{ $product->category ?: '-' }}</span>
                                    </td>
                                    <td>
                                        @if($product->sub_category)
                                            <span class="badge bg-light-info text-info fw-semibold px-2 py-1">{{ $product->sub_category }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-success">
                                        Rp {{ number_format($effectivePrice, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($totalQty > 20)
                                            <span class="badge bg-success fs-6 px-3 py-1">{{ $totalQty }} {{ $product->unit ?: 'pcs' }}</span>
                                        @elseif($totalQty > 0)
                                            <span class="badge bg-warning text-dark fs-6 px-3 py-1">{{ $totalQty }} {{ $product->unit ?: 'pcs' }}</span>
                                        @else
                                            <span class="badge bg-danger fs-6 px-3 py-1">Stok Kosong</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse($product->gudangProducts as $gp)
                                            <div class="small">
                                                <span class="badge bg-light text-dark border me-1">{{ $gp->rack->rak_kode ?? $gp->rack_id }}</span>
                                                <span>({{ $gp->qty }} {{ $product->unit ?: 'pcs' }})</span>
                                            </div>
                                        @empty
                                            <span class="text-muted small">- Belum di Rak -</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('products.destroy', $product->id) }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk master ini beserta seluruh data stoknya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-danger" title="Hapus Produk">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="feather icon-box f-40 d-block mb-3 text-muted"></i>
                                        <h6 class="fw-semibold text-dark mb-1">Belum ada data Master Produk.</h6>
                                        <div class="mt-2 small">
                                            Gunakan tombol <strong>Import Excel</strong> atau <strong>Input Stock</strong> di Halaman Stok Gudang untuk menambahkan produk baru.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <small class="text-muted fw-semibold">
                        Menampilkan {{ $products->firstItem() ?? 0 }} sampai {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} total barang
                    </small>
                    <div>
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold" id="importModalLabel">Import Master Produk (Excel / CSV)</h5>
                    <button type="button" class="btn-close close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3 small">
                        <i class="feather icon-info me-1"></i> Format file Excel 100% sama dengan file spreadsheet <strong>web-led</strong>. Data akan otomatis mensinkronkan master produk dan stok rak.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Upload File Spreadsheet</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text mt-1 text-muted">Format yang didukung: <strong>.xlsx, .xls, .csv</strong></div>
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
@endsection

@push('scripts')
<script>
$(function() {
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

    $(document).on('click', '[data-dismiss="modal"], [data-bs-target="#importModal"]', function (e) {
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
