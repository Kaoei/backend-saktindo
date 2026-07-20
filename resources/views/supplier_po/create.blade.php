@extends('layouts.dashboard', [
    'title' => 'Buat Purchase Order Supplier',
    'pageTitle' => 'Buat Purchase Order Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier-po.index').'">Supplier PO</a></li><li class="breadcrumb-item">Buat</li>',
])

@section('content')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('supplier-po.store') }}" method="POST" id="po-form">
    @csrf
    <div class="row">
        <!-- Main Form -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold">Detail Barang PO</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Nama Barang</th>
                                    <th style="width: 15%;">Qty</th>
                                    <th style="width: 20%;">Harga Unit</th>
                                    <th style="width: 15%;">Diskon (%)</th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="form-select product-select" required>
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}">
                                                    {{ $product->item_name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][qty]" class="form-control qty-input" min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][price]" class="form-control price-input" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][discount]" class="form-control discount-input" min="0" max="100" step="0.1" value="0" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-row"><span class="material-icons" style="font-size: 1.2rem;">delete</span></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-row">
                        <span class="material-icons" style="font-size: 1.1rem; vertical-align: middle;">add</span> Tambah Baris
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-semibold">Informasi PO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Order</label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan tambahan..."></textarea>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3 fw-bold text-dark">
                        <span>Total Akumulasi:</span>
                        <span id="grand-total">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Simpan PO Template</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let rowCount = 1;
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
                    <select name="items[${rowCount}][product_id]" class="form-select product-select" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input" min="1" value="1" required>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][price]" class="form-control price-input" min="0" step="0.01" value="0" required>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][discount]" class="form-control discount-input" min="0" max="100" step="0.1" value="0" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-row"><span class="material-icons" style="font-size: 1.2rem;">delete</span></button>
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
    });
</script>
@endpush
