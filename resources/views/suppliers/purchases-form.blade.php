@extends('layouts.dashboard', [
    'title' => 'Form Riwayat Pembelian',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.purchases').'">Riwayat Pembelian</a></li><li class="breadcrumb-item">Form Riwayat</li>',
])

@section('content')

@php
    $selectedSupplierId = old('supplier_id', $purchaseHistory->supplier_id ?: request('supplier_id'));
@endphp

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Riwayat Pembelian</h5>
                <a href="{{ route('suppliers.purchases') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
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
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input name="purchase_date" type="date" class="form-control" value="{{ old('purchase_date', $purchaseHistory->purchase_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Invoice</label>
                            <input name="invoice_number" type="text" class="form-control" value="{{ old('invoice_number', $purchaseHistory->invoice_number) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Item <span class="text-danger">*</span></label>
                            <input name="item_name" type="text" class="form-control" value="{{ old('item_name', $purchaseHistory->item_name) }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Qty <span class="text-danger">*</span></label>
                            <input name="quantity" type="number" step="0.01" min="0" class="form-control" value="{{ old('quantity', $purchaseHistory->quantity) }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Harga <span class="text-danger">*</span></label>
                            <input name="unit_price" type="number" step="0.01" min="0" class="form-control" value="{{ old('unit_price', $purchaseHistory->unit_price) }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Total</label>
                            <input name="total_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('total_amount', $purchaseHistory->total_amount) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['ordered' => 'Ordered', 'received' => 'Received', 'returned' => 'Returned', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $purchaseHistory->status ?: 'ordered') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $purchaseHistory->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ route('suppliers.purchases') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
