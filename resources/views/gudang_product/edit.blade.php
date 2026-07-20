@extends('layouts.dashboard',
[
    'title' => 'Edit Penempatan Barang di Rak',
    'pageTitle' => 'Produk Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('gudang-product.index').'">Produk Gudang</a></li><li class="breadcrumb-item">Edit Penempatan</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-8">

        <div class="card">

            <div class="card-header">
                <h5>Edit Penempatan Barang: {{ $gudangProduct->id }}</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('gudang-product.update', $gudangProduct->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Barang</label>
                        <input type="text" class="form-control" value="{{ $gudangProduct->supplierProduct->item_name ?? '-' }} (SKU: {{ $gudangProduct->supplierProduct->sku ?? '-' }})" disabled>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Brand</label>
                            <select name="brand" class="form-select">
                                <option value="">-- Pilih Brand --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->name }}" {{ old('brand', $gudangProduct->supplierProduct->brand ?? '') === $brand->name ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori 1</label>
                            <select name="category" id="product-category" class="form-select">
                                <option value="">-- Pilih Kategori 1 --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}" data-id="{{ $category->id }}" {{ old('category', $gudangProduct->supplierProduct->category ?? '') === $category->name ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori 2 (Sub Kategori)</label>
                            <select name="sub_category" id="product-subcategory" class="form-select">
                                <option value="">-- Pilih Kategori 2 --</option>
                                @foreach($subCategories as $subCat)
                                    <option value="{{ $subCat->name }}" data-category-id="{{ $subCat->category_id }}" {{ old('sub_category', $gudangProduct->supplierProduct->sub_category ?? '') === $subCat->name ? 'selected' : '' }}>
                                        {{ $subCat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Stok (Qty)</label>
                        <input type="number" name="qty" class="form-control" value="{{ old('qty', $gudangProduct->qty) }}" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $gudangProduct->price) }}" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Discount</label>
                        <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', $gudangProduct->discount) }}" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Rak Penyimpanan
                        </label>

                        <select name="rack_id"
                                class="form-select"
                                required>

                            <option value="">
                                Pilih Rak
                            </option>

                            @foreach($racks as $rack)
                                <option value="{{ $rack->rak_kode }}" @selected(old('rack_id', $gudangProduct->rack_id) === $rack->rak_kode)>
                                    {{ $rack->rak_kode }}
                                    -
                                    {{ $rack->location }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <button type="submit"
                            class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a href="{{ route('gudang-product.index') }}"
                       class="btn btn-secondary">
                        Batal
                    </a>

                </form>

            </div>

        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('product-category');
        const subcategorySelect = document.getElementById('product-subcategory');
        const originalSubOptions = Array.from(subcategorySelect.options);

        function filterSubcategories() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryId = selectedOption ? selectedOption.getAttribute('data-id') : null;
            const currentSelectedValue = subcategorySelect.value;

            // Clear options
            subcategorySelect.innerHTML = '';

            // Filter options
            originalSubOptions.forEach(option => {
                const optionCategoryId = option.getAttribute('data-category-id');
                // Show if it is placeholder OR if it belongs to selected category ID
                if (!option.value || !categoryId || optionCategoryId === categoryId) {
                    subcategorySelect.appendChild(option);
                }
            });

            // Restore selection if possible, otherwise default to first option
            subcategorySelect.value = currentSelectedValue;
            if (subcategorySelect.selectedIndex === -1) {
                subcategorySelect.selectedIndex = 0;
            }
        }

        categorySelect.addEventListener('change', filterSubcategories);

        // Run on initial load to match old category selection
        if (categorySelect.value) {
            filterSubcategories();
        }
    });
</script>
@endpush
