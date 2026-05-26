@extends('layouts.dashboard', [
    'title' => 'Product Management',
    'pageTitle' => 'Product Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Product Management</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark">Products</h5>
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

                <form action="{{ route('products.index') }}" method="GET" class="mb-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="feather icon-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name, category, product ID, or seller SKU..." value="{{ $search ?? '' }}">
                        <button class="btn btn-primary px-4" type="submit">Search</button>
                        @if($search)
                            <a href="{{ route('products.index') }}" class="btn btn-light-secondary px-4 d-inline-flex align-items-center">Clear</a>
                        @endif
                    </div>
                </form>

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
                                        <h6 class="fw-semibold text-dark mb-1">No products found in the database.</h6>
                                        <div class="mt-2 small">
                                            Import a TikTok batch edit Excel template or click "Add Product" to create one manually.
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