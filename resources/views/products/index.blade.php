@extends('layouts.dashboard', [
    'title' => 'Product Line Up Management',
    'pageTitle' => 'Product Line Up',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Product Line Up</li>',
])

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
                    <button type="button" class="btn btn-light-success d-inline-flex align-items-center justify-content-center gap-1" data-toggle="modal" data-target="#importModal" style="width: 100px !important; flex: none; padding-left: 8px !important; padding-right: 8px !important;">
                        <i class="feather icon-upload fs-6 text-success"></i> Import</button>
                    <a href="{{ route('products.export') }}" class="btn btn-light-primary d-inline-flex align-items-center justify-content-center gap-2" style="width: max-content !important; flex: none;">
                        <i class="material-icons-two-tone text-primary">download</i>
                        Export
                    </a>
                    <a href="{{ route('products.create') }}" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" style="width: max-content !important; flex: none;">
                        <i class="material-icons-two-tone text-white">add_shopping_cart</i>
                        Add Product
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm border-0" role="alert">
                        <i class="feather icon-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm border-0" role="alert">
                        <i class="feather icon-alert-triangle me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
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
                    <table class="table table-hover align-middle m-b-0">
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
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="material-icons-two-tone f-40 d-block mb-3">production_quantity_limits</i>
                                        <h6 class="fw-semibold text-dark mb-1">Tidak ada produk yang cocok dengan filter.</h6>
                                        <div class="mt-2 small">
                                            Coba reset filter atau gunakan kata kunci pencarian yang berbeda.
                                        </div>
                                    </td>
                                </tr>
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

<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="importModalLabel">Import Products</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark">Upload Excel File</label>
                        <p class="text-muted small mb-3">Select the completed spreadsheet (`.xlsx` or `.xls`) to import. The data import will begin parsing product records starting from row 6.</p>
                        
                        <div class="p-5 border border-2 border-dashed rounded text-center bg-light position-relative transition-all hover-bg-white">
                            <i class="material-icons-two-tone f-40 text-success mb-2">upload_file</i>
                            <div class="text-dark"><strong>Drag and drop</strong> or click to select your spreadsheet</div>
                            <input type="file" name="excel_file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept=".xlsx, .xls" required style="cursor: pointer;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        Process Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection