@extends('layouts.dashboard', [
    'title' => 'Product Line Up Management',
    'pageTitle' => 'Product Line Up',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Product Line Up</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">Katalog Product Line Up</h5>
                    <small class="text-muted">Manajemen produk, variasi, dan filter ala marketplace Tokopedia</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-light-success d-inline-flex align-items-center gap-1 shadow-sm px-3" data-toggle="modal" data-target="#importModal">
                        <i class="feather icon-upload text-success me-1"></i> Import Produk
                    </button>
                    <button type="button" class="btn btn-light-primary d-inline-flex align-items-center gap-1 shadow-sm px-3" data-toggle="modal" data-target="#exportModal">
                        <i class="feather icon-download text-primary me-1"></i> Export Produk
                    </button>
                    <a href="{{ route('products.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3">
                        <i class="feather icon-plus me-1"></i> Add Product
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

                <!-- Tokopedia Style Marketplace Filter Bar -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <form action="{{ route('products.index') }}" method="GET" class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="feather icon-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama barang, warna, watt, SKU, ID produk..." value="{{ $search ?? '' }}">
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
                                <select name="stock_status" class="form-select bg-white">
                                    <option value="">-- Status Stok --</option>
                                    <option value="in_stock" {{ ($stockStatus ?? '') == 'in_stock' ? 'selected' : '' }}>Stok Melimpah (&gt;20)</option>
                                    <option value="low_stock" {{ ($stockStatus ?? '') == 'low_stock' ? 'selected' : '' }}>Stok Menipis (1-20)</option>
                                    <option value="out_of_stock" {{ ($stockStatus ?? '') == 'out_of_stock' ? 'selected' : '' }}>Habis (Out of Stock)</option>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                    <i class="feather icon-filter me-1"></i> Filter
                                </button>
                                @if($search || $category || $stockStatus)
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                                        <i class="feather icon-rotate-ccw"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="products-table" class="table table-hover align-middle m-b-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thumbnail</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Variations</th>
                                <th>Price Range</th>
                                <th>Total Stock</th>
                                <th class="text-end" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        @if($product->main_image)
                                            <img src="{{ $product->main_image }}" class="rounded shadow-sm" alt="Product Image" style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px;">
                                                <i class="material-icons-two-tone text-muted f-20">photo</i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $product->product_name }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-primary text-primary">{{ $product->category ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-info text-info">{{ $product->variations_count }} SKUs</span>
                                    </td>
                                    <td class="fw-bold text-success">
                                        @if($product->min_price == $product->max_price)
                                            Rp {{ number_format($product->min_price, 0, ',', '.') }}
                                        @else
                                            Rp {{ number_format($product->min_price, 0, ',', '.') }} - Rp {{ number_format($product->max_price, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->total_quantity > 20)
                                            <span class="badge bg-light-success text-success">{{ $product->total_quantity }} units</span>
                                        @elseif($product->total_quantity > 0)
                                            <span class="badge bg-light-warning text-warning">{{ $product->total_quantity }} units</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-icon btn-light-success me-1" title="Edit">
                                            <i class="feather icon-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('products.destroy', $product->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product and all its variations?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <small class="text-muted fw-semibold">
                        Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} entries
                    </small>
                    <div>
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Produk -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title text-white d-flex align-items-center font-weight-bold" id="importModalLabel">
                    <i class="feather icon-upload me-2 fs-5"></i> Import Data Produk via Excel
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; outline: none; cursor: pointer;">
                    <i class="feather icon-x" style="font-size: 16px;"></i>
                </button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <!-- Step 1: Download Template -->
                    <div class="card bg-light border-0 shadow-sm mb-4">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <div class="fw-bold text-dark font-size-sm mb-1 d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-2" style="width: 22px; height: 22px; line-height: 16px;">1</span>
                                    Unduh Template Excel Format Terbaru
                                </div>
                                <small class="text-muted">Gunakan template resmi agar header kolom sesuai secara otomatis.</small>
                            </div>
                            <a href="{{ route('products.download-template') }}" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">
                                <i class="feather icon-download me-1"></i> Unduh Template (.xlsx)
                            </a>
                        </div>
                    </div>

                    <!-- Step 2: Upload File -->
                    <div class="mb-3">
                        <div class="fw-bold text-dark font-size-sm mb-2 d-flex align-items-center">
                            <span class="badge bg-primary rounded-circle me-2" style="width: 22px; height: 22px; line-height: 16px;">2</span>
                            Upload File Spreadsheet (.xlsx / .xls)
                        </div>

                        <label for="excel_file_input" class="p-4 border border-2 border-dashed rounded text-center bg-white d-block hover-bg-light transition-all cursor-pointer mb-0">
                            <i class="feather icon-file-text f-36 text-primary mb-2 d-block"></i>
                            <div class="text-dark fw-semibold" id="import-upload-label">
                                Drag and drop file Excel Anda di sini, atau <span class="text-primary text-decoration-underline">Pilih File</span>
                            </div>
                            <small class="text-muted d-block mt-1">Format didukung: .xlsx, .xls (Mulai membaca baris data dari baris 6)</small>
                            <input type="file" name="excel_file" id="excel_file_input" class="d-none" accept=".xlsx, .xls" required>
                        </label>

                        <div id="file-selected-info" class="alert alert-success d-none mt-3 mb-0 align-items-center justify-content-between py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="feather icon-check-circle me-2 fs-5"></i>
                                <span id="selected-file-name" class="fw-semibold text-truncate" style="max-width: 350px;"></span>
                            </div>
                            <button type="button" class="btn-close" id="btn-clear-file" style="font-size: 0.8rem;"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm fw-semibold">
                        <i class="feather icon-check-circle me-1"></i> Proses Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Export Produk -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title text-white d-flex align-items-center font-weight-bold" id="exportModalLabel">
                    <i class="feather icon-download me-2 fs-5"></i> Export Katalog Produk
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; outline: none; cursor: pointer;">
                    <i class="feather icon-x" style="font-size: 16px;"></i>
                </button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-primary">
                    <i class="feather icon-file-text" style="font-size: 48px;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-2">Export Semua Data Produk & Variasi</h6>
                <p class="text-secondary small mb-4">File Excel yang di-export menggunakan format standar TikTok Seller Center yang telah dirapikan secara otomatis (termasuk styling header dan auto-width).</p>

                <div class="d-grid gap-2">
                    <a href="{{ route('products.export') }}" class="btn btn-primary btn-lg rounded-pill shadow-sm fw-semibold">
                        <i class="feather icon-download me-2"></i> Download Export Excel (.xlsx)
                    </a>
                    <a href="{{ route('products.download-template') }}" class="btn btn-outline-secondary rounded-pill mt-2">
                        <i class="feather icon-file me-1"></i> Unduh Blank Template Saja
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-dismiss="modal">Tutup</button>
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
                pageLength: 10,
                order: [[1, 'asc']],
                language: { emptyTable: 'Tidak ada produk yang cocok.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 6] }
                ]
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('excel_file_input');
            const fileInfo = document.getElementById('file-selected-info');
            const fileNameDisplay = document.getElementById('selected-file-name');
            const clearBtn = document.getElementById('btn-clear-file');
            const uploadLabel = document.getElementById('import-upload-label');

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        fileNameDisplay.textContent = this.files[0].name;
                        fileInfo.classList.remove('d-none');
                        fileInfo.classList.add('d-flex');
                        uploadLabel.innerHTML = 'File terpilih: <span class="text-success fw-bold">' + this.files[0].name + '</span>';
                    }
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        fileInput.value = '';
                        fileInfo.classList.add('d-none');
                        fileInfo.classList.remove('d-flex');
                        uploadLabel.innerHTML = 'Drag and drop file Excel Anda di sini, atau <span class="text-primary text-decoration-underline">Pilih File</span>';
                    });
                }
            }
        });
    </script>
@endpush
