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

@if(isset($prefillItems) && $prefillItems->count() > 0)
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="feather icon-info me-2 f-20"></i>
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
        <div class="col-xl-9 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0 fw-semibold text-dark">Detail Barang PO</h5>
                        <small class="text-muted">Mendukung diskon bertingkat hingga 4 level (Contoh: 15% + 10% + 5% + 5%)</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;" class="fw-semibold">NAMA BARANG</th>
                                    <th style="width: 85px;" class="text-center fw-semibold">QTY</th>
                                    <th style="width: 130px;" class="text-center fw-semibold">HARGA UNIT</th>
                                    <th style="min-width: 260px;" class="text-center fw-semibold">DISKON BERTINGKAT (%)</th>
                                    <th style="width: 130px;" class="text-end fw-semibold">SUBTOTAL</th>
                                    <th class="text-center" style="width: 60px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @if(isset($prefillItems) && $prefillItems->count() > 0)
                                    @foreach($prefillItems as $idx => $prefill)
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select product-select" required>
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
                                            <input type="number" name="items[{{ $idx }}][qty]" class="form-control qty-input text-center px-1" min="1" value="{{ $prefill['qty'] }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="{{ $prefill['price'] }}" required>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="number" name="items[{{ $idx }}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_1'] ?? $prefill['discount'] ?? 0 }}" placeholder="D1 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_2'] ?? 0 }}" placeholder="D2 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_3'] ?? 0 }}" placeholder="D3 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_4'] ?? 0 }}" placeholder="D4 %">
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
                                    @endforeach
                                @else
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
                                        <input type="number" name="items[0][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
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
                                @endif
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
                        <label class="form-label fw-medium text-dark">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Tanggal Order <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan tambahan...">{{ isset($prefillItems) && $prefillItems->count() > 0 ? 'PO dibuat dari data kekurangan stok dashboard.' : '' }}</textarea>
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
    $(document).ready(function() {
        let rowCount = $('.item-row').length;
        const products = @json($products);

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

            $row.find('.line-subtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(lineSubtotal)));
            return lineSubtotal;
        }

        function updateGrandTotal() {
            let total = 0;
            $('.item-row').each(function() {
                total += calculateLineSubtotal($(this));
            });
            $('#grand-total').text('Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(total)));
        }

        // Add row
        $('#add-row').on('click', function() {
            let optionsHtml = '<option value="">-- Pilih Barang --</option>';
            products.forEach(p => {
                optionsHtml += `<option value="${p.id}" data-price="${p.last_purchase_price}">${p.item_name} (${p.sku})</option>`;
            });

            const trHtml = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowCount}][product_id]" class="form-select product-select" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
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

            const $newRow = $(trHtml);
            $('#items-body').append($newRow);
            rowCount++;
            bindRowEvents($newRow);

            if (window.initSelect2) {
                window.initSelect2($newRow.find('select'));
            }
        });

        // Remove row
        $(document).on('click', '.remove-row', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                updateGrandTotal();
            } else {
                alert('Minimal harus ada 1 baris item PO.');
            }
        });

        function bindRowEvents($row) {
            $row.find('.product-select').on('change select2:select', function() {
                const selectedOpt = $(this).find('option:selected');
                const price = selectedOpt.attr('data-price') || 0;
                $row.find('.price-input').val(price);
                updateGrandTotal();
            });

            $row.find('.qty-input, .price-input, .disc1-input, .disc2-input, .disc3-input, .disc4-input').on('input change', function() {
                updateGrandTotal();
            });
        }

        // Bind initial rows
        $('.item-row').each(function() {
            bindRowEvents($(this));
        });

        // Calculate grand total on page load
        updateGrandTotal();
    });
</script>
@endpush
