@extends('layouts.dashboard', [
    'title' => 'Buat Purchase Order Supplier',
    'pageTitle' => 'Buat Purchase Order Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier-po.index').'">Supplier PO</a></li><li class="breadcrumb-item">Buat</li>',
])

@section('content')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(isset($prefillItems) && $prefillItems->count() > 0)
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="feather icon-info mr-2 f-20"></i>
            <div>
                <strong>Data dari Dashboard Kekurangan Stok</strong><br>
                <small>{{ $prefillItems->count() }} barang sudah terisi otomatis berdasarkan data kekurangan stok. Silakan pilih Supplier, review, dan simpan PO.</small>
            </div>
        </div>
    </div>
@endif

<form action="{{ route('supplier-po.store') }}" method="POST" id="po-form" class="mt-4">
    @csrf
    <div class="row">
        <!-- Main Form -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-semibold text-dark">Detail Barang PO</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="items-table">
                            <thead class="bg-light">
                                <tr>
                                    <th style="min-width: 220px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Nama Barang</th>
                                    <th style="width: 100px; min-width: 85px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;" class="text-center">Qty</th>
                                    <th style="width: 140px; min-width: 120px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;" class="text-center">Harga Unit</th>
                                    <th style="width: 100px; min-width: 85px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;" class="text-center">Diskon (%)</th>
                                    <th class="text-center" style="width: 70px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @if(isset($prefillItems) && $prefillItems->count() > 0)
                                    @foreach($prefillItems as $idx => $prefill)
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select product-select" required style="font-size: 0.9rem;">
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}"
                                                        {{ $prefill['product_id'] == $product->id ? 'selected' : '' }}>
                                                        {{ $product->item_name }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][qty]" class="form-control qty-input text-center px-1" min="1" value="{{ $prefill['qty'] }}" required style="font-size: 0.9rem;">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][price]" class="form-control price-input text-end px-2" min="0" step="0.01" value="{{ $prefill['price'] }}" required style="font-size: 0.9rem;">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][discount]" class="form-control discount-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount'] }}" required style="font-size: 0.9rem;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris"><i class="material-icons-two-tone" style="font-size: 18px; vertical-align: middle;">delete</i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="form-select product-select" required style="font-size: 0.9rem;">
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}">
                                                    {{ $product->item_name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required style="font-size: 0.9rem;">
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][price]" class="form-control price-input text-end px-2" min="0" step="0.01" value="0" required style="font-size: 0.9rem;">
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][discount]" class="form-control discount-input text-center px-1" min="0" max="100" step="0.1" value="0" required style="font-size: 0.9rem;">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris"><i class="material-icons-two-tone" style="font-size: 18px; vertical-align: middle;">delete</i></button>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3 fw-semibold" id="add-row">
                        <i class="material-icons-two-tone" style="font-size: 16px; vertical-align: middle;">add</i> Tambah Baris Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold text-dark">Informasi PO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required style="font-size: 0.9rem;">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Tanggal Order <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required style="font-size: 0.9rem;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan tambahan..." style="font-size: 0.9rem;">{{ isset($prefillItems) && $prefillItems->count() > 0 ? 'PO dibuat dari data kekurangan stok dashboard.' : '' }}</textarea>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold text-dark">Total Akumulasi:</span>
                        <span id="grand-total" class="fw-bold text-primary fs-5">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Simpan PO Template</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let rowCount = document.querySelectorAll('.item-row').length;
        const products = @json($products);

        function updateGrandTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
                
                const lineTotal = qty * price * (1 - discount / 100);
                total += lineTotal;
            });
            document.getElementById('grand-total').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        // Add row
        document.getElementById('add-row').addEventListener('click', function() {
            const tbody = document.getElementById('items-body');
            const newRow = document.createElement('tr');
            newRow.className = 'item-row';
            
            // Build products options
            let optionsHtml = '<option value="">-- Pilih Barang --</option>';
            products.forEach(p => {
                optionsHtml += `<option value="${p.id}" data-price="${p.last_purchase_price}">${p.item_name} (${p.sku})</option>`;
            });

            newRow.innerHTML = `
                <td>
                    <select name="items[${rowCount}][product_id]" class="form-select product-select" required style="font-size: 0.9rem;">
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required style="font-size: 0.9rem;">
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][price]" class="form-control price-input text-end px-2" min="0" step="0.01" value="0" required style="font-size: 0.9rem;">
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][discount]" class="form-control discount-input text-center px-1" min="0" max="100" step="0.1" value="0" required style="font-size: 0.9rem;">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris"><i class="material-icons-two-tone" style="font-size: 18px; vertical-align: middle;">delete</i></button>
                </td>
            `;
            tbody.appendChild(newRow);
            rowCount++;
            bindRowEvents(newRow);
        });

        // Remove row
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                    updateGrandTotal();
                } else {
                    alert('Minimal harus ada 1 baris item PO.');
                }
            }
        });

        function bindRowEvents(row) {
            // Auto fill price on product select
            row.querySelector('.product-select').addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const price = option.getAttribute('data-price') || 0;
                row.querySelector('.price-input').value = price;
                updateGrandTotal();
            });

            // Recalculate on inputs edit
            row.querySelector('.qty-input').addEventListener('input', updateGrandTotal);
            row.querySelector('.price-input').addEventListener('input', updateGrandTotal);
            row.querySelector('.discount-input').addEventListener('input', updateGrandTotal);
        }

        // Bind initial row
        document.querySelectorAll('.item-row').forEach(row => {
            bindRowEvents(row);
        });

        // Calculate grand total on page load (for prefilled items)
        updateGrandTotal();
    });
</script>
@endpush
