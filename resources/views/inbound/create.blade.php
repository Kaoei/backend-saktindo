@extends('layouts.dashboard',
[
    'title' => 'Tambah Barang Masuk',
    'pageTitle' => 'Barang Masuk',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('inbound.index').'">Barang Masuk</a></li><li class="breadcrumb-item">Tambah Barang Masuk</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-1">Tambah Barang Masuk</h5>
                    <small class="text-muted">Input barang dari supplier</small>
                </div>

                <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>

            </div>

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

                <form method="POST" action="{{ route('inbound.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Gunakan Template Supplier PO (Opsional)
                            </label>
                            <select name="supplier_po_id" id="po-select" class="form-select">
                                <option value="">-- Pilih PO Supplier --</option>
                                @foreach($supplierPos as $po)
                                    <option value="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}" data-items='@json($po->items)'>
                                        {{ $po->po_number }} - {{ $po->supplier->name ?? 'Supplier' }} (Rp {{ number_format($po->total_amount, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Supplier <span class="text-danger">*</span>
                            </label>

                            <select name="supplier_id"
                                    class="form-select"
                                    required>

                                <option value="">Pilih Supplier</option>

                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Produk Supplier <span class="text-danger">*</span>
                            </label>

                            <select name="supplier_product_id"
                                    class="form-select"
                                    required>

                                <option value="">Pilih Produk</option>

                                @foreach($supplierProducts as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->sku }} - {{ $product->item_name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Qty Diterima <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="qty_received"
                                   class="form-control"
                                   min="1"
                                   value="{{ old('qty_received') }}"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                HPP (Harga Pokok Penjualan)
                            </label>

                            <input type="number"
                                   name="hpp"
                                   id="hpp-input"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('hpp', 0) }}">
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Tanggal Masuk <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="received_date"
                                   class="form-control"
                                   value="{{ old('received_date', date('Y-m-d')) }}"
                                   required>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">

                        <button type="submit"
                                class="btn btn-primary">
                            Simpan Barang Masuk
                        </button>

                        <a href="{{ route('inbound.index') }}"
                           class="btn btn-outline-secondary">
                            Batal
                        </a>

                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const poSelect = document.getElementById('po-select');
        const supplierSelect = document.querySelector('select[name="supplier_id"]');
        const productSelect = document.querySelector('select[name="supplier_product_id"]');
        const qtyInput = document.querySelector('input[name="qty_received"]');
        const hppInput = document.getElementById('hpp-input');

        poSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (!option.value) return;

            const supplierId = option.getAttribute('data-supplier-id');
            const items = JSON.parse(option.getAttribute('data-items') || '[]');

            if (supplierId) {
                supplierSelect.value = supplierId;
            }

            if (items.length > 0) {
                // Auto fill with first item details
                const firstItem = items[0];
                productSelect.value = firstItem.supplier_product_id;
                qtyInput.value = firstItem.qty;
                
                // HPP: price after discount
                const price = parseFloat(firstItem.price) || 0;
                const discount = parseFloat(firstItem.discount) || 0;
                const netPrice = price * (1 - discount / 100);
                hppInput.value = netPrice.toFixed(2);
            }
        });
    });
</script>
@endpush