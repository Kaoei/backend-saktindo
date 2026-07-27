@extends('layouts.dashboard',
[
    'title' => 'Edit Barang Masuk',
    'pageTitle' => 'Barang Masuk',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('inbound.index').'">Barang Masuk</a></li><li class="breadcrumb-item">Edit Barang Masuk</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-semibold">Edit Barang Masuk: {{ $inbound->id }}</h5>
                    <small class="text-muted">
                        Status:
                        <span class="badge bg-secondary font-monospace">{{ $inbound->status }}</span>
                    </small>
                </div>

                <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>
            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('inbound.update', $inbound->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID Inbound</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $inbound->id }}"
                                   disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="received_date"
                                   class="form-control @error('received_date') is-invalid @enderror"
                                   value="{{ old('received_date', $inbound->received_date) }}"
                                   required>
                            @error('received_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id"
                                     class="form-select @error('supplier_id') is-invalid @enderror"
                                     required>
                                <option value="">Pilih Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', $inbound->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Produk Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_product_id"
                                     class="form-select @error('supplier_product_id') is-invalid @enderror"
                                     required>
                                <option value="">Pilih Produk Supplier</option>
                                @foreach($supplierProducts as $product)
                                    <option value="{{ $product->id }}"
                                        {{ old('supplier_product_id', $inbound->supplier_product_id) == $product->id ? 'selected' : '' }}>
                                        {{ $product->sku }} - {{ $product->item_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gunakan Template Supplier PO (Opsional)</label>
                            <select name="supplier_po_id" class="form-select @error('supplier_po_id') is-invalid @enderror">
                                <option value="">-- Tanpa PO --</option>
                                @foreach($supplierPos as $po)
                                    <option value="{{ $po->id }}" {{ old('supplier_po_id', $inbound->supplier_po_id) == $po->id ? 'selected' : '' }}>
                                        {{ $po->po_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_po_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">HPP (Harga Pokok Penjualan)</label>
                            <input type="number"
                                   name="hpp"
                                   class="form-control @error('hpp') is-invalid @enderror"
                                   value="{{ old('hpp', $inbound->hpp) }}"
                                   min="0"
                                   step="0.01">
                            @error('hpp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-success fw-bold">Qty Baik / Diterima <span class="text-danger">*</span></label>
                            <input type="number"
                                   name="qty_received"
                                   class="form-control @error('qty_received') is-invalid @enderror"
                                   value="{{ old('qty_received', $inbound->qty_received) }}"
                                   min="0"
                                   required>
                            @error('qty_received') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label text-warning fw-bold">Qty Rusak</label>
                            <input type="number"
                                   name="qty_damaged"
                                   class="form-control @error('qty_damaged') is-invalid @enderror"
                                   value="{{ old('qty_damaged', $inbound->qty_damaged) }}"
                                   min="0">
                            @error('qty_damaged') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label text-danger fw-bold">Qty Tidak Ada / Kurang</label>
                            <input type="number"
                                   name="qty_missing"
                                   class="form-control @error('qty_missing') is-invalid @enderror"
                                   value="{{ old('qty_missing', $inbound->qty_missing) }}"
                                   min="0">
                            @error('qty_missing') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status"
                                     class="form-select @error('status') is-invalid @enderror"
                                     required>
                                <option value="pending" {{ old('status', $inbound->status) == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="stored" {{ old('status', $inbound->status) == 'stored' ? 'selected' : '' }}>
                                    Stored
                                </option>
                                <option value="cancelled" {{ old('status', $inbound->status) == 'cancelled' ? 'selected' : '' }}>
                                    Cancelled
                                </option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Catatan Kondisi Barang</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan mengenai kondisi barang">{{ old('notes', $inbound->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            Update Barang Masuk
                        </button>

                        <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

@endsection