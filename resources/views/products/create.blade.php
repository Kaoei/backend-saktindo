@extends('layouts.dashboard', [
    'title' => 'Add Product',
    'pageTitle' => 'Product Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('products.index').'">Product Management</a></li><li class="breadcrumb-item">Add Product</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Add Product</h5>
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('products.store') }}">
                    @csrf

                    <!-- Datalists for Master Data -->
                    <datalist id="master-variants-list">
                        @if(isset($masterVariants))
                            @foreach($masterVariants as $mVar)
                                <option value="{{ $mVar->name }}"></option>
                            @endforeach
                        @endif
                    </datalist>

                    <datalist id="master-categories-list">
                        @if(isset($categories))
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}"></option>
                            @endforeach
                        @endif
                    </datalist>

                    <datalist id="master-brands-list">
                        @if(isset($brands))
                            @foreach($brands as $br)
                                <option value="{{ $br->name }}"></option>
                            @endforeach
                        @endif
                    </datalist>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                                <i class="material-icons-two-tone f-18 me-1 align-middle">info</i> General Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="variation-tab" data-toggle="tab" href="#variation" role="tab" aria-controls="variation" aria-selected="false">
                                <i class="feather icon-sliders f-18 me-1 align-middle"></i> Variations & Pricing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="shipping-tab" data-toggle="tab" href="#shipping" role="tab" aria-controls="shipping" aria-selected="false">
                                <i class="feather icon-package f-18 me-1 align-middle"></i> Package & Shipping
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="images-tab" data-toggle="tab" href="#images" role="tab" aria-controls="images" aria-selected="false">
                                <i class="feather icon-image f-18 me-1 align-middle"></i> Images
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="specifications-tab" data-toggle="tab" href="#specifications" role="tab" aria-controls="specifications" aria-selected="false">
                                <i class="feather icon-settings f-18 me-1 align-middle"></i> Custom Properties
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
                                    <select name="product_name" id="product_name_select" class="form-select select2-tags" required data-placeholder="-- Cari / Pilih Produk Gudang atau Ketik Nama Produk --">
                                        <option value="">-- Cari / Pilih Produk Gudang atau Ketik Nama Produk --</option>
                                        @php
                                            $oldName = old('product_name');
                                            $gudangNames = [];
                                        @endphp
                                        @if(isset($gudangProducts))
                                            @foreach($gudangProducts as $gProd)
                                                @php
                                                    $itemName = $gProd->supplierProduct?->item_name ?? $gProd->id;
                                                    $gudangNames[] = $itemName;
                                                    $catName = $gProd->supplierProduct?->category ?? '';
                                                    $subCatName = $gProd->supplierProduct?->sub_category ?? '';
                                                    $brandName = $gProd->supplierProduct?->brand ?? '';
                                                    $skuName = $gProd->supplierProduct?->sku ?? $gProd->id;
                                                @endphp
                                                <option value="{{ $itemName }}" 
                                                    data-category="{{ $catName }}" 
                                                    data-sub-category="{{ $subCatName }}" 
                                                    data-brand="{{ $brandName }}"
                                                    {{ $oldName === $itemName ? 'selected' : '' }}>
                                                    {{ $itemName }} (SKU Gudang: {{ $skuName }})
                                                </option>
                                            @endforeach
                                        @endif
                                        @if($oldName && !in_array($oldName, $gudangNames))
                                            <option value="{{ $oldName }}" selected>{{ $oldName }}</option>
                                        @endif
                                    </select>
                                    <small class="text-muted">Pilih dari Produk Gudang (otomatis mengisi Kategori, Sub Kategori & Brand) atau ketik nama produk baru.</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Category</label>
                                    <select name="category" id="category_select" class="form-select select2-tags" data-placeholder="-- Pilih / Ketik Kategori --">
                                        <option value="">-- Pilih / Ketik Kategori --</option>
                                        @php
                                            $oldCat = old('category');
                                            $catNames = [];
                                        @endphp
                                        @if(isset($categories))
                                            @foreach($categories as $cat)
                                                @php $catNames[] = $cat->name; @endphp
                                                <option value="{{ $cat->name }}" {{ $oldCat === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        @endif
                                        @if($oldCat && !in_array($oldCat, $catNames))
                                            <option value="{{ $oldCat }}" selected>{{ $oldCat }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Sub Category</label>
                                    <select name="sub_category" id="sub_category_select" class="form-select select2-tags" data-placeholder="-- Pilih / Ketik Sub Kategori --">
                                        <option value="">-- Pilih / Ketik Sub Kategori --</option>
                                        @php
                                            $oldSubCat = old('sub_category');
                                            $subCatNames = [];
                                        @endphp
                                        @if(isset($subCategories))
                                            @foreach($subCategories as $subCat)
                                                @php $subCatNames[] = $subCat->name; @endphp
                                                <option value="{{ $subCat->name }}" data-category="{{ $subCat->category?->name }}" {{ $oldSubCat === $subCat->name ? 'selected' : '' }}>
                                                    {{ $subCat->name }} {{ $subCat->category ? '('.$subCat->category->name.')' : '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                        @if($oldSubCat && !in_array($oldSubCat, $subCatNames))
                                            <option value="{{ $oldSubCat }}" selected>{{ $oldSubCat }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Brand</label>
                                    <select name="brand" id="brand_select" class="form-select select2-tags" data-placeholder="-- Pilih / Ketik Brand --">
                                        <option value="">-- Pilih / Ketik Brand --</option>
                                        @php
                                            $oldBrand = old('brand');
                                            $brandNames = [];
                                        @endphp
                                        @if(isset($brands))
                                            @foreach($brands as $br)
                                                @php $brandNames[] = $br->name; @endphp
                                                <option value="{{ $br->name }}" {{ $oldBrand === $br->name ? 'selected' : '' }}>{{ $br->name }}</option>
                                            @endforeach
                                        @endif
                                        @if($oldBrand && !in_array($oldBrand, $brandNames))
                                            <option value="{{ $oldBrand }}" selected>{{ $oldBrand }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Product Description</label>
                                    <textarea name="product_description" class="form-control" rows="8" placeholder="HTML and text descriptive details...">{{ old('product_description') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">TikTok Product ID</label>
                                    <input name="product_id" type="text" class="form-control" value="{{ old('product_id') }}" placeholder="e.g. 19-digit TikTok ID (optional)">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">TikTok Product URL</label>
                                    <input name="tts_product_url" type="url" class="form-control" value="{{ old('tts_product_url') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tokopedia Product URL</label>
                                    <input name="toko_product_url" type="url" class="form-control" value="{{ old('toko_product_url') }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: VARIATIONS & PRICING -->
                        <div class="tab-pane fade" id="variation" role="tabpanel" aria-labelledby="variation-tab">
                            <p class="text-muted small">Configure multiple variants (e.g. colors, sizes, or spec options). Every product must have at least one variation.</p>
                            
                            @if(isset($masterVariants) && $masterVariants->count() > 0)
                                <div class="card bg-light border-0 shadow-sm mb-4">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="fw-bold text-dark font-size-sm d-flex align-items-center">
                                                <i class="feather icon-layers me-2 text-primary"></i> Pilih dari Master Varian
                                            </div>
                                            <a href="{{ route('variants.index') }}" target="_blank" class="text-primary small text-decoration-none fw-semibold">
                                                <i class="feather icon-external-link me-1"></i> Kelola Master Varian
                                            </a>
                                        </div>
                                        <div class="row align-items-center g-2">
                                            <div class="col-md-9 col-sm-8">
                                                <select id="quick-master-variant-select" class="form-select no-select2" multiple data-placeholder="Cari & pilih varian master (bisa pilih banyak)...">
                                                    @foreach($masterVariants as $mVar)
                                                        <option value="{{ $mVar->name }}">{{ $mVar->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 col-sm-4">
                                                <button type="button" id="btn-batch-add-variants" class="btn btn-primary w-100 shadow-sm">
                                                    <i class="feather icon-plus-circle me-1"></i> Tambahkan
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 d-block"><i class="feather icon-info me-1"></i> Cari & pilih satu atau beberapa varian master di atas lalu klik Tambahkan.</small>
                                    </div>
                                </div>
                            @endif

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
                                        <tr>
                                            <td>
                                                <input name="variations[0][variation_value]" type="text" class="form-control" value="Default" list="master-variants-list" required placeholder="Pilih/ketik varian">
                                            </td>
                                            <td>
                                                <input name="variations[0][price]" type="number" class="form-control" value="0" required min="0">
                                            </td>
                                            <td>
                                                <input name="variations[0][quantity]" type="number" class="form-control" value="0" required min="0">
                                            </td>
                                            <td>
                                                <input name="variations[0][sku_id]" type="text" class="form-control" placeholder="Auto generated">
                                            </td>
                                            <td>
                                                <input name="variations[0][seller_sku]" type="text" class="form-control" placeholder="Internal code">
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-icon btn-light-danger remove-var-btn"><i class="feather icon-trash-2"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-sm btn-light-primary mb-4" id="add-variation-btn">
                                <i class="feather icon-plus"></i> Add Variation
                            </button>

                            <div class="row border-top pt-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Minimum Sales Quantity</label>
                                    <input name="minimum_order_quantity" type="number" class="form-control" value="{{ old('minimum_order_quantity', 1) }}" min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Support COD (Cash on Delivery)</label>
                                    <select name="cod" class="form-select">
                                        <option value="Y" {{ old('cod') === 'Y' ? 'selected' : '' }}>Yes (Y)</option>
                                        <option value="N" {{ old('cod') === 'N' ? 'selected' : '' }}>No (N)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Pre-sale Handling Time (Days)</label>
                                    <input name="pre_order_time" type="number" class="form-control" value="{{ old('pre_order_time') }}" min="0" placeholder="Optional">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: PACKAGE & SHIPPING -->
                        <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Weight (grams) <span class="text-danger">*</span></label>
                                    <input name="parcel_weight" type="number" class="form-control" value="{{ old('parcel_weight', 0) }}" required min="0">
                                    <small class="text-muted">Weight of the product including package box/wrapping.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Length (cm)</label>
                                    <input name="parcel_length" type="number" class="form-control" value="{{ old('parcel_length') }}" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Width (cm)</label>
                                    <input name="parcel_width" type="number" class="form-control" value="{{ old('parcel_width') }}" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Package Height (cm)</label>
                                    <input name="parcel_height" type="number" class="form-control" value="{{ old('parcel_height') }}" min="0">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Size Chart URL</label>
                                    <input name="size_chart" type="url" class="form-control" value="{{ old('size_chart') }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: IMAGES -->
                        <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">
                            <p class="text-muted small">We recommend hosting images on your public storage or TikTok Media Center. Enter image URLs below.</p>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Main Image URL <span class="text-danger">*</span></label>
                                    <input name="main_image" type="url" class="form-control" value="{{ old('main_image') }}" required placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 2 URL</label>
                                    <input name="image_2" type="url" class="form-control" value="{{ old('image_2') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 3 URL</label>
                                    <input name="image_3" type="url" class="form-control" value="{{ old('image_3') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 4 URL</label>
                                    <input name="image_4" type="url" class="form-control" value="{{ old('image_4') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 5 URL</label>
                                    <input name="image_5" type="url" class="form-control" value="{{ old('image_5') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 6 URL</label>
                                    <input name="image_6" type="url" class="form-control" value="{{ old('image_6') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 7 URL</label>
                                    <input name="image_7" type="url" class="form-control" value="{{ old('image_7') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 8 URL</label>
                                    <input name="image_8" type="url" class="form-control" value="{{ old('image_8') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Image 9 URL</label>
                                    <input name="image_9" type="url" class="form-control" value="{{ old('image_9') }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: SPECIFICATIONS & PROPERTIES -->
                        <div class="tab-pane fade" id="specifications" role="tabpanel" aria-labelledby="specifications-tab">
                            <p class="text-muted small">TikTok-specific properties depending on categories. Enter dropdown values or plain parameters.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Warranty Type</label>
                                    <input name="warranty_type" type="text" class="form-control" value="{{ old('warranty_type') }}" placeholder="e.g. Garansi Resmi">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">With Battery</label>
                                    <input name="with_battery" type="text" class="form-control" value="{{ old('with_battery') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Battery In The Product</label>
                                    <input name="battery_in_the_product" type="text" class="form-control" value="{{ old('battery_in_the_product') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">With Magnet</label>
                                    <input name="with_magnet" type="text" class="form-control" value="{{ old('with_magnet') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Plug Type</label>
                                    <input name="plug_type" type="text" class="form-control" value="{{ old('plug_type') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Product Condition</label>
                                    <input name="product_condition" type="text" class="form-control" value="{{ old('product_condition') }}" placeholder="e.g. Baru">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Materials</label>
                                    <input name="materials" type="text" class="form-control" value="{{ old('materials') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Run Time</label>
                                    <input name="run_time" type="text" class="form-control" value="{{ old('run_time') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Robot Vacuum Features</label>
                                    <input name="robot_vacuum_features" type="text" class="form-control" value="{{ old('robot_vacuum_features') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Iron Features</label>
                                    <input name="iron_features" type="text" class="form-control" value="{{ old('iron_features') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Contains Dangerous Goods?</label>
                                    <input name="contains_dangerous_goods" type="text" class="form-control" value="{{ old('contains_dangerous_goods') }}">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">SNI Certificate Link/HTML</label>
                                    <textarea name="sni_certificate" class="form-control" rows="3" placeholder="Certificate text or URL...">{{ old('sni_certificate') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 text-end">
                        <button type="reset" class="btn btn-light-secondary me-2">Reset Form</button>
                        <button type="submit" class="btn btn-primary px-4">Save Product</button>
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
        $('.select2-tags').select2({
            theme: 'bootstrap-5',
            tags: true,
            allowClear: true,
            width: '100%'
        });

        $('#product_name_select').on('change', function() {
            let selectedOpt = $(this).find(':selected');
            let cat = selectedOpt.data('category');
            let subCat = selectedOpt.data('sub-category');
            let brand = selectedOpt.data('brand');

            if (cat && $('#category_select').length) {
                if ($('#category_select option[value="' + cat + '"]').length === 0) {
                    let newOption = new Option(cat, cat, true, true);
                    $('#category_select').append(newOption).trigger('change');
                } else {
                    $('#category_select').val(cat).trigger('change');
                }
            }

            if (subCat && $('#sub_category_select').length) {
                if ($('#sub_category_select option[value="' + subCat + '"]').length === 0) {
                    let newOption = new Option(subCat, subCat, true, true);
                    $('#sub_category_select').append(newOption).trigger('change');
                } else {
                    $('#sub_category_select').val(subCat).trigger('change');
                }
            }

            if (brand && $('#brand_select').length) {
                if ($('#brand_select option[value="' + brand + '"]').length === 0) {
                    let newOption = new Option(brand, brand, true, true);
                    $('#brand_select').append(newOption).trigger('change');
                } else {
                    $('#brand_select').val(brand).trigger('change');
                }
            }
        });

        if ($('#quick-master-variant-select').length) {
            $('#quick-master-variant-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari & pilih varian master (bisa pilih banyak)...',
                allowClear: true,
                width: '100%'
            });
        }

        function appendVariationRow(variantValue = '') {
            let html = `
                <tr>
                    <td>
                        <input name="variations[${varIndex}][variation_value]" type="text" class="form-control" value="${variantValue}" list="master-variants-list" placeholder="Pilih/ketik varian" required>
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
        }

        $('#add-variation-btn').on('click', function() {
            appendVariationRow('');
        });

        $('#btn-batch-add-variants').on('click', function() {
            let selectedVariants = $('#quick-master-variant-select').val();
            if (!selectedVariants || selectedVariants.length === 0) {
                alert('Silakan pilih setidaknya satu varian master dari dropdown pencarian.');
                return;
            }

            selectedVariants.forEach(function(variantName) {
                let firstRowInput = $('#variations-container tr:first-child input[name$="[variation_value]"]');
                if ($('#variations-container tr').length === 1 && (firstRowInput.val() === 'Default' || firstRowInput.val() === '')) {
                    firstRowInput.val(variantName);
                } else {
                    appendVariationRow(variantName);
                }
            });

            $('#quick-master-variant-select').val(null).trigger('change');
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
