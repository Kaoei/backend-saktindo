@extends('layouts.dashboard', [
    'title' => 'Edit Purchase Order Supplier',
    'pageTitle' => 'Edit Purchase Order Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier-po.index').'">Supplier PO</a></li><li class="breadcrumb-item">Edit</li>',
])

@section('content')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('supplier-po.update', $supplierPo->id) }}" method="POST" id="po-form" class="mt-4">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Main Form -->
        <div class="col-xl-9 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-semibold text-dark">Detail Barang PO — <span class="text-primary">{{ $supplierPo->po_number }}</span></h5>
                        <small class="text-muted">Mendukung diskon bertingkat hingga 4 level dan Paket Promo Bundling</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bundleModal" data-toggle="modal" data-target="#bundleModal" id="btn-open-bundle-modal">
                            <i class="feather icon-gift me-1"></i>
                            Pilih Promo Bundling
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;" class="fw-semibold">NAMA BARANG</th>
                                    <th style="width: 120px;" class="text-center fw-semibold">QTY</th>
                                    <th style="width: 130px;" class="text-center fw-semibold">HARGA UNIT</th>
                                    <th style="min-width: 260px;" class="text-center fw-semibold">DISKON BERTINGKAT (%)</th>
                                    <th style="width: 130px;" class="text-end fw-semibold">SUBTOTAL</th>
                                    <th class="text-center" style="width: 60px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @forelse($supplierPo->items as $idx => $it)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $idx }}][product_id]" class="form-select product-select" required>
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}"
                                                    {{ $it->supplier_product_id == $product->id ? 'selected' : '' }}>
                                                    {{ $product->item_name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                            <input type="number" name="items[{{ $idx }}][qty]" class="form-control qty-input text-center px-1" min="1" value="{{ $it->qty }}" required>
                                            <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="{{ $it->price }}" required>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="number" name="items[{{ $idx }}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="{{ $it->discount_1 ?? $it->discount ?? 0 }}" placeholder="D1 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $idx }}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="{{ $it->discount_2 ?? 0 }}" placeholder="D2 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $idx }}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="{{ $it->discount_3 ?? 0 }}" placeholder="D3 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $idx }}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="{{ $it->discount_4 ?? 0 }}" placeholder="D4 %">
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success line-subtotal">
                                        Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
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
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                            <input type="number" name="items[0][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
                                            <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="number" name="items[0][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success line-subtotal">
                                        Rp 0
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="add-row">
                            <i class="feather icon-plus me-1"></i> Tambah Baris Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold text-dark">Informasi PO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">No. PO</label>
                        <input type="text" class="form-control bg-light" value="{{ $supplierPo->po_number }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Status PO <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ old('status', $supplierPo->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="received" {{ old('status', $supplierPo->status) === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ old('status', $supplierPo->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $supplierPo->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">No. Referensi / Surat Jalan</label>
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $supplierPo->reference_number) }}" placeholder="Contoh: REF/2026/08/012">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Tanggal PO <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $supplierPo->order_date ? $supplierPo->order_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan khusus PO...">{{ old('notes', $supplierPo->notes) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Diskon Tambahan (Nominal / Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" step="any" min="0" name="additional_discount" id="additional-discount" class="form-control text-end" value="{{ old('additional_discount', (float)($supplierPo->additional_discount ?? 0)) }}" placeholder="0">
                        </div>
                        <small class="text-muted">Potongan nominal langsung pada subtotal</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Pilihan Pajak <span class="text-danger">*</span></label>
                        <select name="tax_type" id="tax-type" class="form-select">
                            <option value="non_pajak" {{ old('tax_type', $supplierPo->tax_type ?? 'non_pajak') === 'non_pajak' ? 'selected' : '' }}>Non Pajak (Tanpa PPN)</option>
                            <option value="pajak" {{ old('tax_type', $supplierPo->tax_type ?? '') === 'pajak' ? 'selected' : '' }}>Pajak (PPN)</option>
                        </select>
                    </div>

                    @php
                        $isTaxActive = old('tax_type', $supplierPo->tax_type ?? 'non_pajak') === 'pajak';
                    @endphp
                    <div class="mb-3" id="tax-amount-container" style="{{ $isTaxActive ? '' : 'display: none;' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium text-dark mb-0">Nominal Pajak PPN (Rp)</label>
                            <button type="button" class="btn btn-xs btn-link text-decoration-none p-0 text-primary" id="btn-calc-tax-11" style="font-size: 0.78rem;">
                                <i class="feather icon-refresh-cw me-1"></i>Otomatis 11%
                            </button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" step="any" min="0" name="tax_amount" id="tax-amount" class="form-control text-end" value="{{ old('tax_amount', (float)($supplierPo->tax_amount ?? 0)) }}" placeholder="0">
                        </div>
                        <small class="text-muted">Bisa di-input / diedit secara manual</small>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Subtotal:</span>
                        <span class="fw-medium text-dark small" id="summary-subtotal">Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-1" id="summary-discount-row" style="display: none;">
                        <span class="text-muted small">Diskon Tambahan:</span>
                        <span class="fw-medium text-danger small" id="summary-discount">- Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2" id="summary-tax-row" style="display: none;">
                        <span class="text-muted small">Pajak (PPN):</span>
                        <span class="fw-medium text-dark small" id="summary-tax">+ Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                        <span class="fw-semibold text-dark">Grand Total:</span>
                        <span class="fw-bold text-success fs-5" id="grand-total">Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="feather icon-save me-1"></i> Perbarui Supplier PO
                        </button>
                        <a href="{{ route('supplier-po.index') }}" class="btn btn-light py-2">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Pilih Promo Bundling -->
<div class="modal fade" id="bundleModal" tabindex="-1" aria-labelledby="bundleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold text-dark" id="bundleModalLabel">
                    <i class="feather icon-gift text-success me-2"></i>Pilih Paket Promo Bundling
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div id="bundle-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Memuat daftar paket promo aktif...</div>
                </div>

                <div id="bundle-list-container" style="display: none;">
                    <p class="text-muted small mb-3">Pilih salah satu paket bundling di bawah dan tentukan jumlah paket yang ingin dipesan. Komponen produk beserta harga promo akan otomatis ditambahkan ke form PO.</p>
                    <div class="list-group" id="bundle-items-list">
                        <!-- Dynamic items -->
                    </div>
                </div>

                <div id="bundle-empty" class="text-center py-4 text-muted" style="display: none;">
                    <i class="feather icon-info mb-2" style="font-size: 2rem;"></i>
                    <div class="fw-semibold">Tidak ada promo bundling yang aktif saat ini.</div>
                    <small>Anda dapat membuat promo baru di menu <a href="{{ route('bundle-promos.create') }}" target="_blank">Master Promo Bundling</a>.</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(function() {
        let rowCount = {{ max(1, $supplierPo->items->count()) }};
        const products = @json($products);
        let activeBundles = [];

        function calculateLineSubtotal($row) {
            const qty = parseFloat($row.find('.qty-input').val()) || 0;
            const price = parseFloat($row.find('.price-input').val()) || 0;

            const d1 = parseFloat($row.find('.disc1-input').val()) || 0;
            const d2 = parseFloat($row.find('.disc2-input').val()) || 0;
            const d3 = parseFloat($row.find('.disc3-input').val()) || 0;
            const d4 = parseFloat($row.find('.disc4-input').val()) || 0;

            // Compounded 4-tier discount formula
            const netUnitPrice = price * (1 - d1 / 100) * (1 - d2 / 100) * (1 - d3 / 100) * (1 - d4 / 100);
            const lineSubtotal = netUnitPrice * qty;

            $row.find('.line-subtotal').text('Rp ' + Math.round(lineSubtotal).toLocaleString('id-ID'));
            return lineSubtotal;
        }

        function calculateGrandTotal() {
            let itemsSubtotal = 0;
            $('#items-body .item-row').each(function() {
                itemsSubtotal += calculateLineSubtotal($(this));
            });

            const additionalDiscount = parseFloat($('#additional-discount').val()) || 0;
            const taxType = $('#tax-type').val();
            let taxAmount = parseFloat($('#tax-amount').val()) || 0;

            if (taxType === 'non_pajak') {
                taxAmount = 0;
                $('#tax-amount-container').slideUp(150);
                $('#summary-tax-row').hide();
            } else {
                $('#tax-amount-container').slideDown(150);
                if (taxAmount > 0) {
                    $('#summary-tax-row').show();
                    $('#summary-tax').text('+ Rp ' + Math.round(taxAmount).toLocaleString('id-ID'));
                } else {
                    $('#summary-tax-row').hide();
                }
            }

            const dpp = Math.max(0, itemsSubtotal - additionalDiscount);
            const grandTotal = dpp + (taxType === 'pajak' ? taxAmount : 0);

            $('#summary-subtotal').text('Rp ' + Math.round(itemsSubtotal).toLocaleString('id-ID'));

            if (additionalDiscount > 0) {
                $('#summary-discount-row').show();
                $('#summary-discount').text('- Rp ' + Math.round(additionalDiscount).toLocaleString('id-ID'));
            } else {
                $('#summary-discount-row').hide();
            }

            $('#grand-total').text('Rp ' + Math.round(grandTotal).toLocaleString('id-ID'));
        }

        $('#tax-type').on('change', function() {
            if ($(this).val() === 'pajak') {
                let itemsSubtotal = 0;
                $('#items-body .item-row').each(function() {
                    itemsSubtotal += calculateLineSubtotal($(this));
                });
                const additionalDiscount = parseFloat($('#additional-discount').val()) || 0;
                const dpp = Math.max(0, itemsSubtotal - additionalDiscount);
                const currentTax = parseFloat($('#tax-amount').val()) || 0;
                if (currentTax === 0) {
                    const defaultTax = Math.round(dpp * 0.11);
                    $('#tax-amount').val(defaultTax);
                }
            }
            calculateGrandTotal();
        });

        $('#btn-calc-tax-11').on('click', function(e) {
            e.preventDefault();
            let itemsSubtotal = 0;
            $('#items-body .item-row').each(function() {
                itemsSubtotal += calculateLineSubtotal($(this));
            });
            const additionalDiscount = parseFloat($('#additional-discount').val()) || 0;
            const dpp = Math.max(0, itemsSubtotal - additionalDiscount);
            const defaultTax = Math.round(dpp * 0.11);
            $('#tax-amount').val(defaultTax);
            calculateGrandTotal();
        });

        $('#additional-discount, #tax-amount').on('input change', function() {
            calculateGrandTotal();
        });

        // Initialize calculations
        calculateGrandTotal();

        // Plus/Minus Qty Buttons
        $(document).on('click', '.btn-qty-plus', function() {
            const $input = $(this).closest('.input-group').find('.qty-input');
            let val = parseInt($input.val()) || 0;
            $input.val(val + 1).trigger('input');
        });

        $(document).on('click', '.btn-qty-minus', function() {
            const $input = $(this).closest('.input-group').find('.qty-input');
            let val = parseInt($input.val()) || 1;
            if (val > 1) {
                $input.val(val - 1).trigger('input');
            }
        });

        // Add New Row
        $('#add-row').on('click', function() {
            let options = '<option value="">-- Pilih Barang --</option>';
            products.forEach(p => {
                options += `<option value="${p.id}" data-price="${p.last_purchase_price}">${p.item_name} (${p.sku})</option>`;
            });

            const newRow = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowCount}][product_id]" class="form-select product-select" required>
                            ${options}
                        </select>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                            <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
                            <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                        </div>
                    </td>
                    <td>
                        <input type="number" name="items[${rowCount}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="0" required>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <input type="number" name="items[${rowCount}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                        </div>
                    </td>
                    <td class="text-end fw-bold text-success line-subtotal">
                        Rp 0
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                            <i class="feather icon-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#items-body').append(newRow);
            rowCount++;
            calculateGrandTotal();
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#items-body .item-row').length > 1) {
                $(this).closest('tr').remove();
                calculateGrandTotal();
            } else {
                alert('Minimal satu barang harus ada di dalam PO.');
            }
        });

        // Auto-fill price on product selection
        $(document).on('change', '.product-select', function() {
            const price = $(this).find(':selected').data('price') || 0;
            const $row = $(this).closest('tr');
            $row.find('.price-input').val(price);
            calculateGrandTotal();
        });

        // Recalculate on any input change
        $(document).on('input', '.qty-input, .price-input, .disc1-input, .disc2-input, .disc3-input, .disc4-input', function() {
            calculateGrandTotal();
        });

        // ==========================================
        // PROMO BUNDLING INTEGRATION
        // ==========================================
        $('#btn-open-bundle-modal').on('click', function() {
            loadActiveBundles();
        });

        function loadActiveBundles() {
            $('#bundle-loading').show();
            $('#bundle-list-container').hide();
            $('#bundle-empty').hide();

            $.ajax({
                url: '{{ route("bundle-promos.api.active") }}',
                method: 'GET',
                success: function(response) {
                    $('#bundle-loading').hide();
                    activeBundles = response.data || [];

                    if (activeBundles.length === 0) {
                        $('#bundle-empty').show();
                        return;
                    }

                    renderBundleList(activeBundles);
                    $('#bundle-list-container').show();
                },
                error: function(err) {
                    $('#bundle-loading').hide();
                    alert('Gagal memuat data paket promo bundling.');
                }
            });
        }

        function renderBundleList(bundles) {
            let html = '';
            bundles.forEach((bundle, bIdx) => {
                let itemsListHtml = '';
                bundle.items.forEach(item => {
                    const priceFormatted = 'Rp ' + Math.round(item.unit_price).toLocaleString('id-ID');
                    itemsListHtml += `
                        <div class="d-flex justify-content-between text-muted small py-1 border-bottom">
                            <span><i class="feather icon-check text-success me-1"></i> ${item.product_name} (${item.quantity} ${item.unit || 'pcs'})</span>
                            <span>@ ${priceFormatted}</span>
                        </div>
                    `;
                });

                const promoBadge = bundle.discount_type === 'percentage' 
                    ? `<span class="badge bg-warning text-dark me-1"><i class="feather icon-percent me-1"></i>Diskon ${bundle.discount_value}%</span>`
                    : `<span class="badge bg-success me-1"><i class="feather icon-tag me-1"></i>Harga Khusus</span>`;

                const bundleTotalFormatted = 'Rp ' + Math.round(bundle.bundle_price).toLocaleString('id-ID');

                html += `
                    <div class="list-group-item p-3 mb-2 border rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">${bundle.name} ${promoBadge}</h6>
                                <div class="text-muted small">${bundle.description || 'Paket bundling promo spesial'}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">Total Harga Paket:</div>
                                <div class="fw-bold text-success fs-6">${bundleTotalFormatted}</div>
                            </div>
                        </div>

                        <div class="bg-light p-2 rounded mb-3">
                            <div class="small fw-semibold text-secondary mb-1">Daftar Produk Termasuk:</div>
                            ${itemsListHtml}
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <span class="small text-muted">Jumlah Paket:</span>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <input type="number" class="form-control text-center bundle-multiplier" id="bundle-mult-${bIdx}" value="1" min="1">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary btn-add-bundle-to-po" data-bundle-index="${bIdx}">
                                <i class="feather icon-plus me-1"></i>Tambahkan ke PO
                            </button>
                        </div>
                    </div>
                `;
            });

            $('#bundle-items-list').html(html);
        }

        $(document).on('click', '.btn-add-bundle-to-po', function() {
            const bIdx = $(this).data('bundle-index');
            const bundle = activeBundles[bIdx];
            if (!bundle) return;

            const multiplier = parseInt($('#bundle-mult-' + bIdx).val()) || 1;
            if (multiplier < 1) {
                alert('Jumlah paket minimal 1');
                return;
            }

            bundle.items.forEach(item => {
                let options = '<option value="">-- Pilih Barang --</option>';
                products.forEach(p => {
                    const isSelected = (p.id == item.supplier_product_id) ? 'selected' : '';
                    options += `<option value="${p.id}" data-price="${p.last_purchase_price}" ${isSelected}>${p.item_name} (${p.sku})</option>`;
                });

                const itemQty = item.quantity * multiplier;
                const itemPrice = item.unit_price;

                const newRow = `
                    <tr class="item-row bg-light-primary">
                        <td>
                            <select name="items[${rowCount}][product_id]" class="form-select product-select border-primary" required>
                                ${options}
                            </select>
                            <small class="text-success d-block mt-1"><i class="feather icon-gift me-1"></i>Paket: ${bundle.name}</small>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="${itemQty}" required>
                                <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="items[${rowCount}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="${itemPrice}" required>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" name="items[${rowCount}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                            </div>
                        </td>
                        <td class="text-end fw-bold text-success line-subtotal">
                            Rp 0
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                <i class="feather icon-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;

                $('#items-body').append(newRow);
                rowCount++;
            });

            calculateGrandTotal();

            const modalEl = document.getElementById('bundleModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
            } else {
                $('#bundleModal').modal('hide');
            }
        });
    });
</script>
@endpush
