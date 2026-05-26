@extends('layouts.dashboard', [
    'title' => 'Edit Product',
    'pageTitle' => 'Product Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('products.index').'">Product Management</a></li><li class="breadcrumb-item">Edit Product</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Edit Product: {{ $product->product_name }}</h5>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>
            
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('products.update', $product->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">info</i> General Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="variation-tab" data-toggle="tab" href="#variation" role="tab" aria-controls="variation" aria-selected="false">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">sell</i> Variations & Pricing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="shipping-tab" data-toggle="tab" href="#shipping" role="tab" aria-controls="shipping" aria-selected="false">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">local_shipping</i> Package & Shipping
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="images-tab" data-toggle="tab" href="#images" role="tab" aria-controls="images" aria-selected="false">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">photo_library</i> Images
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="specifications-tab" data-toggle="tab" href="#specifications" role="tab" aria-controls="specifications" aria-selected="false">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">tune</i> Custom Properties
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content border p-4 rounded bg-white" id="productTabsContent">
                        
                        <!-- TAB 1: GENERAL INFO -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                                    <input name="product_name" type="text" class="form-control" value="{{ old('product_name', $product->product_name) }}" required placeholder="e.g. Kabel telpon Supreme INDOOR isi 12">
                                    <small class="text-muted">Must be consistent across all variations of the product.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Category</label>
                                    <input name="category" type="text" class="form-control" value="{{ old('category', $product->category) }}" placeholder="e.g. Household Appliance Parts & Accessories (601106)">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Brand</label>
                                    <input name="brand" type="text" class="form-control" value="{{ old('brand', $product->brand) }}" placeholder="e.g. Supreme">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Product Description</label>
                                    <textarea name="product_description" class="form-control" rows="8" placeholder="HTML and text descriptive details...">{{ old('product_description', $product->product_description) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">TikTok Product ID</label>
                                    <input name="product_id" type="text" class="form-control" value="{{ old('product_id', $product->product_id) }}" placeholder="TikTok original ID">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">TikTok Product URL</label>
                                    <input name="tts_product_url" type="url" class="form-control" value="{{ old('tts_product_url', $product->tts_product_url) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tokopedia Product URL</label>
                                    <input name="toko_product_url" type="url" class="form-control" value="{{ old('toko_product_url', $product->toko_product_url) }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: VARIATIONS & PRICING -->
                        <div class="tab-pane fade" id="variation" role="tabpanel" aria-labelledby="variation-tab">
                            <p class="text-muted small">Configure multiple variants (e.g. colors, sizes, or spec options). Every product must have at least one variation.</p>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Variation Name <span class="text-danger">*</span></th>
                                            <th>Price (Rp) <span class="text-danger">*</span></th>
                                            <th>Stock Quantity <span class="text-danger">*</span></th>
                                            <th>TikTok SKU ID</th>
                                            <th>Seller SKU</th>
                                            <th class="text-end" style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variations-container">
                                        @foreach($variations as $index => $var)
                                            <tr>
                                                <td>
                                                    <input name="variations[{{ $index }}][variation_value]" type="text" class="form-control" value="{{ $var->variation_value }}" required>
                                                </td>
                                                <td>
                                                    <input name="variations[{{ $index }}][price]" type="number" class="form-control" value="{{ intval($var->price) }}" required min="0">
                                                </td>
                                                <td>
                                                    <input name="variations[{{ $index }}][quantity]" type="number" class="form-control" value="{{ $var->quantity }}" required min="0">
                                                </td>
                                                <td>
                                                    <input name="variations[{{ $index }}][sku_id]" type="text" class="form-control" value="{{ $var->sku_id }}" placeholder="Auto generated">
                                                </td>
                                                <td>
                                                    <input name="variations[{{ $index }}][seller_sku]" type="text" class="form-control" value="{{ $var->seller_sku }}" placeholder="Internal code">
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-icon btn-light-danger remove-var-btn"><i class="feather icon-trash-2"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-sm btn-light-primary mb-4" id="add-variation-btn">
                                <i class="feather icon-plus"></i> Add Variation
                            </button>

                            <div class="row border-top pt-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Minimum Sales Quantity</label>
                                    <input name="minimum_order_quantity" type="number" class="form-control" value="{{ old('minimum_order_quantity', $product->minimum_order_quantity) }}" min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Support COD (Cash on Delivery)</label>
                                    <select name="cod" class="form-select">
                                        <option value="Y" {{ old('cod', $product->cod) === 'Y' ? 'selected' : '' }}>Yes (Y)</option>
                                        <option value="N" {{ old('cod', $product->cod) === 'N' ? 'selected' : '' }}>No (N)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Pre-sale Handling Time (Days)</label>
                                    <input name="pre_order_time" type="number" class="form-control" value="{{ old('pre_order_time', $product->pre_order_time) }}" min="0" placeholder="Optional">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: PACKAGE & SHIPPING -->
                        <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Weight (grams) <span class="text-danger">*</span></label>
                                    <input name="parcel_weight" type="number" class="form-control" value="{{ old('parcel_weight', $product->parcel_weight) }}" required min="0">
                                    <small class="text-muted">Weight of the product including package box/wrapping.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Length (cm)</label>
                                    <input name="parcel_length" type="number" class="form-control" value="{{ old('parcel_length', $product->parcel_length) }}" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Width (cm)</label>
                                    <input name="parcel_width" type="number" class="form-control" value="{{ old('parcel_width', $product->parcel_width) }}" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Height (cm)</label>
                                    <input name="parcel_height" type="number" class="form-control" value="{{ old('parcel_height', $product->parcel_height) }}" min="0">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Size Chart URL</label>
                                    <input name="size_chart" type="url" class="form-control" value="{{ old('size_chart', $product->size_chart) }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: IMAGES -->
                        <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">
                            <p class="text-muted small">We recommend hosting images on your public storage or TikTok Media Center. Enter image URLs below.</p>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Main Image URL <span class="text-danger">*</span></label>
                                    <input name="main_image" type="url" class="form-control" value="{{ old('main_image', $product->main_image) }}" required placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 2 URL</label>
                                    <input name="image_2" type="url" class="form-control" value="{{ old('image_2', $product->image_2) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 3 URL</label>
                                    <input name="image_3" type="url" class="form-control" value="{{ old('image_3', $product->image_3) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 4 URL</label>
                                    <input name="image_4" type="url" class="form-control" value="{{ old('image_4', $product->image_4) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 5 URL</label>
                                    <input name="image_5" type="url" class="form-control" value="{{ old('image_5', $product->image_5) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 6 URL</label>
                                    <input name="image_6" type="url" class="form-control" value="{{ old('image_6', $product->image_6) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 7 URL</label>
                                    <input name="image_7" type="url" class="form-control" value="{{ old('image_7', $product->image_7) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 8 URL</label>
                                    <input name="image_8" type="url" class="form-control" value="{{ old('image_8', $product->image_8) }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 9 URL</label>
                                    <input name="image_9" type="url" class="form-control" value="{{ old('image_9', $product->image_9) }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: SPECIFICATIONS & PROPERTIES -->
                        <div class="tab-pane fade" id="specifications" role="tabpanel" aria-labelledby="specifications-tab">
                            <p class="text-muted small">TikTok-specific properties depending on categories. Enter dropdown values or plain parameters.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Warranty Type</label>
                                    <input name="warranty_type" type="text" class="form-control" value="{{ old('warranty_type', $product->warranty_type) }}" placeholder="e.g. Garansi Resmi">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">With Battery</label>
                                    <input name="with_battery" type="text" class="form-control" value="{{ old('with_battery', $product->with_battery) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Battery In The Product</label>
                                    <input name="battery_in_the_product" type="text" class="form-control" value="{{ old('battery_in_the_product', $product->battery_in_the_product) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">With Magnet</label>
                                    <input name="with_magnet" type="text" class="form-control" value="{{ old('with_magnet', $product->with_magnet) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Plug Type</label>
                                    <input name="plug_type" type="text" class="form-control" value="{{ old('plug_type', $product->plug_type) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Product Condition</label>
                                    <input name="product_condition" type="text" class="form-control" value="{{ old('product_condition', $product->product_condition) }}" placeholder="e.g. Baru">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Materials</label>
                                    <input name="materials" type="text" class="form-control" value="{{ old('materials', $product->materials) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Run Time</label>
                                    <input name="run_time" type="text" class="form-control" value="{{ old('run_time', $product->run_time) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Robot Vacuum Features</label>
                                    <input name="robot_vacuum_features" type="text" class="form-control" value="{{ old('robot_vacuum_features', $product->robot_vacuum_features) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Iron Features</label>
                                    <input name="iron_features" type="text" class="form-control" value="{{ old('iron_features', $product->iron_features) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Contains Dangerous Goods?</label>
                                    <input name="contains_dangerous_goods" type="text" class="form-control" value="{{ old('contains_dangerous_goods', $product->contains_dangerous_goods) }}">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">SNI Certificate Link/HTML</label>
                                    <textarea name="sni_certificate" class="form-control" rows="3" placeholder="Certificate text or URL...">{{ old('sni_certificate', $product->sni_certificate) }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">Update Product</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let varIndex = {{ count($variations) }};

        $('#add-variation-btn').on('click', function() {
            let html = `
                <tr>
                    <td>
                        <input name="variations[${varIndex}][variation_value]" type="text" class="form-control" placeholder="e.g. Merah" required>
                    </td>
                    <td>
                        <input name="variations[${varIndex}][price]" type="number" class="form-control" value="0" required min="0">
                    </td>
                    <td>
                        <input name="variations[${varIndex}][quantity]" type="number" class="form-control" value="0" required min="0">
                    </td>
                    <td>
                        <input name="variations[${varIndex}][sku_id]" type="text" class="form-control" placeholder="Auto generated">
                    </td>
                    <td>
                        <input name="variations[${varIndex}][seller_sku]" type="text" class="form-control" placeholder="Internal code">
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-light-danger remove-var-btn"><i class="feather icon-trash-2"></i></button>
                    </td>
                </tr>
            `;
            $('#variations-container').append(html);
            varIndex++;
        });

        $(document).on('click', '.remove-var-btn', function() {
            if ($('#variations-container tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('Product must have at least one variation.');
            }
        });
    });
</script>
@endpush
