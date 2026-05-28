@extends('layouts.dashboard', [
    'title' => 'Form Barang Supplier',
    'pageTitle' => 'Master Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.products').'">Barang Supplier</a></li><li class="breadcrumb-item">Form Barang</li>',
])

@section('content')

@php
    $selectedSupplierId = old('supplier_id', $product->supplier_id ?: request('supplier_id'));
@endphp

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Barang Supplier</h5>
                <a href="{{ route('suppliers.products') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="" disabled {{ $selectedSupplierId ? '' : 'selected' }}>Pilih supplier...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ $selectedSupplierId === $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}{{ $supplier->vendor_code ? ' - '.$supplier->vendor_code : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">SKU</label>
                            <input name="sku" type="text" class="form-control" value="{{ old('sku', $product->sku) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Part Number</label>
                            <input name="part_number" type="text" class="form-control" value="{{ old('part_number', $product->part_number) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input name="item_name" type="text" class="form-control" value="{{ old('item_name', $product->item_name) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kategori</label>
                            <input name="category" type="text" class="form-control" value="{{ old('category', $product->category) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Merk</label>
                            <input name="brand" type="text" class="form-control" value="{{ old('brand', $product->brand) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Satuan</label>
                            <input name="unit" type="text" class="form-control" value="{{ old('unit', $product->unit) }}" placeholder="pcs, roll, meter">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Harga Terakhir</label>
                            <input name="last_purchase_price" type="number" min="0" step="0.01" class="form-control" value="{{ old('last_purchase_price', $product->last_purchase_price ?? 0) }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">MOQ</label>
                            <input name="minimum_order_qty" type="number" min="1" class="form-control" value="{{ old('minimum_order_qty', $product->minimum_order_qty ?? 1) }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Lead Time</label>
                            <div class="input-group">
                                <input name="lead_time_days" type="number" min="0" class="form-control" value="{{ old('lead_time_days', $product->lead_time_days ?? 0) }}">
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $product->status ?: 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $product->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ route('suppliers.products') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
