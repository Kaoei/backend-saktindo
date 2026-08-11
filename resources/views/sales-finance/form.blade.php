@extends('layouts.dashboard', [
    'title' => 'Form Input SO',
    'pageTitle' => 'Sales Order (SO)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">Input SO</li>',
])

@section('content')
@php
    $items = old('items', $order->exists ? $order->items->map(fn ($item) => [
        'product_code' => $item->product_code,
        'product_name' => $item->product_name,
        'unit' => $item->unit,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
        'discount_1' => $item->discount_1 ?? 0,
        'discount_2' => $item->discount_2 ?? 0,
        'discount_3' => $item->discount_3 ?? 0,
        'discount_4' => $item->discount_4 ?? 0,
    ])->toArray() : [['product_code' => '', 'product_name' => '', 'unit' => 'pcs', 'quantity' => 1, 'unit_price' => 0, 'discount_1' => 0, 'discount_2' => 0, 'discount_3' => 0, 'discount_4' => 0]]);
@endphp

<div class="row">
    <div class="col-12">
        <form method="POST" action="{{ $action }}" class="card">
            @csrf
            @if($method !== 'POST') @method($method) @endif
            <div class="card-header">
                <h5 class="mb-0">Form Input SO (Sales Order)</h5>
                <small class="text-muted">Data ini menjadi dasar Sales, pengecekan stok, pengumpulan invoice bulanan, Surat Jalan, dan proses SJS.</small>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <!-- Baris Pertama -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer Terdaftar</label>
                        <select name="customer_id" class="form-select" id="customer_select">
                            <option value="">Manual / belum terdaftar</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $order->customer_id) === $customer->id)>{{ $customer->nama_customer }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama Customer <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name', $order->customer_name) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nomor PI / PO Customer <span class="text-danger">*</span></label>
                        <input type="text" name="customer_po_number" class="form-control @error('customer_po_number') is-invalid @enderror" value="{{ old('customer_po_number', $order->customer_po_number) }}" required>
                    </div>

                    <!-- Baris Kedua -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal PO</label>
                        <input type="date" name="po_date" class="form-control" value="{{ old('po_date', optional($order->po_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal Order <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status Order</label>
                        <select name="order_status" class="form-select">
                            @foreach(['draft', 'stock_check', 'ready_invoice', 'pending_stock', 'invoiced', 'partial_delivery', 'delivered', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(old('order_status', $order->order_status ?: 'draft') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipe Sales <span class="text-danger">*</span></label>
                        <select name="sales_type" class="form-select @error('sales_type') is-invalid @enderror" required>
                            <option value="" disabled @selected(!old('sales_type', $order->sales_type))>-- Pilih Tipe --</option>
                            <option value="js" @selected(old('sales_type', $order->sales_type) === 'js')>JS</option>
                            <option value="sjb" @selected(old('sales_type', $order->sales_type) === 'sjb')>SJB</option>
                            <option value="nearby_store" @selected(old('sales_type', $order->sales_type) === 'nearby_store')>Nearby Store</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-2 mb-2">
                    <div>
                        <h6 class="mb-0">Detail Item Pesanan</h6>
                        <small class="text-muted">Barang diambil dari stok Gudang yang tersimpan di rak.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-item">
                        <i class="material-icons-two-tone">add</i>
                        Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Nama Produk <span class="text-danger">*</span></th>
                                <th style="width: 8%;">Unit</th>
                                <th style="width: 10%;">Stok Barang</th>
                                <th style="width: 9%;">Qty <span class="text-danger">*</span></th>
                                <th style="width: 13%;">Harga Unit (Rp) <span class="text-danger">*</span></th>
                                <th style="width: 20%;">Diskon (D1% + D2% + D3% + D4%)</th>
                                <th style="width: 12%;">Subtotal (Rp)</th>
                                <th style="width: 3%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                @php
                                    $selectedCode = $item['product_code'] ?? '';
                                    $selectedProduct = $productOptions->firstWhere('id', $selectedCode);
                                    $qty = (float) ($item['quantity'] ?? 1);
                                    $price = (float) ($item['unit_price'] ?? ($selectedProduct?->last_purchase_price ?? 0));
                                    $d1 = (float) ($item['discount_1'] ?? 0);
                                    $d2 = (float) ($item['discount_2'] ?? 0);
                                    $d3 = (float) ($item['discount_3'] ?? 0);
                                    $d4 = (float) ($item['discount_4'] ?? 0);
                                    $netPrice = $price * (1 - $d1/100) * (1 - $d2/100) * (1 - $d3/100) * (1 - $d4/100);
                                    $lineTotal = $qty * $netPrice;
                                @endphp
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $index }}][product_code]" class="form-select product-select" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach($productOptions as $product)
                                                <option value="{{ $product->id }}"
                                                    data-name="{{ $product->item_name }}"
                                                    data-unit="{{ $product->unit ?: 'pcs' }}"
                                                    data-price="{{ (float) $product->last_purchase_price }}"
                                                    data-stock="{{ (float) ($product->available_stock ?? 0) }}"
                                                    @selected($selectedCode === $product->id)>
                                                    {{ $product->item_name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="items[{{ $index }}][product_name]" class="product-name-input" value="{{ $item['product_name'] ?? $selectedProduct?->item_name }}">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][unit]" class="form-control unit-input" value="{{ $item['unit'] ?? ($selectedProduct->unit ?? 'pcs') }}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control stock-display text-end" value="{{ number_format((float) ($selectedProduct->available_stock ?? 0), 2, ',', '.') }}" readonly>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" class="form-control qty-input text-center" value="{{ $qty }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" class="form-control price-input text-end" value="{{ $price }}" required>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="number" name="items[{{ $index }}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="{{ $d1 }}" placeholder="D1%">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $index }}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="{{ $d2 }}" placeholder="D2%">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $index }}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="{{ $d3 }}" placeholder="D3%">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[{{ $index }}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="{{ $d4 }}" placeholder="D4%">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control total-display text-end fw-semibold" value="{{ number_format($lineTotal, 2, ',', '.') }}" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item p-1" title="Hapus Baris"><i class="material-icons-two-tone">delete</i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('sales-finance.index') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        let itemIndex = {{ count($items) }};
        const productOptions = @json($productOptions);

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function calculateRowTotal($row) {
            const qty = parseFloat($row.find('.qty-input').val()) || 0;
            const price = parseFloat($row.find('.price-input').val()) || 0;

            const d1 = parseFloat($row.find('.disc1-input').val()) || 0;
            const d2 = parseFloat($row.find('.disc2-input').val()) || 0;
            const d3 = parseFloat($row.find('.disc3-input').val()) || 0;
            const d4 = parseFloat($row.find('.disc4-input').val()) || 0;

            const netUnitPrice = price * (1 - d1 / 100) * (1 - d2 / 100) * (1 - d3 / 100) * (1 - d4 / 100);
            const lineTotal = qty * netUnitPrice;

            $row.find('.total-display').val(formatNumber(lineTotal));
        }

        function buildProductOptionsHtml(selectedId = '') {
            let options = '<option value="">-- Pilih Produk --</option>';
            productOptions.forEach(p => {
                const selected = p.id === selectedId ? 'selected' : '';
                const skuText = p.sku ? ` (${p.sku})` : '';
                const nameEsc = String(p.item_name || '').replace(/"/g, '&quot;');
                options += `<option value="${p.id}" data-name="${nameEsc}" data-unit="${p.unit || 'pcs'}" data-price="${p.last_purchase_price || 0}" data-stock="${p.available_stock || 0}" ${selected}>${nameEsc}${skuText}</option>`;
            });
            return options;
        }

        $('#add-item').on('click', function () {
            const optionsHtml = buildProductOptionsHtml();
            const rowHtml = `
                <tr class="item-row">
                    <td>
                        <select name="items[${itemIndex}][product_code]" class="form-select product-select" required>
                            ${optionsHtml}
                        </select>
                        <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name-input" value="">
                    </td>
                    <td>
                        <input type="text" name="items[${itemIndex}][unit]" class="form-control unit-input" value="pcs" required>
                    </td>
                    <td>
                        <input type="text" class="form-control stock-display text-end" value="0,00" readonly>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][quantity]" class="form-control qty-input text-center" value="1" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_price]" class="form-control price-input text-end" value="0" required>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <input type="number" name="items[${itemIndex}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1%">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${itemIndex}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2%">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${itemIndex}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3%">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${itemIndex}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4%">
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control total-display text-end fw-semibold" value="0,00" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item p-1" title="Hapus Baris"><i class="material-icons-two-tone">delete</i></button>
                    </td>
                </tr>
            `;
            $('#items-table tbody').append(rowHtml);
            itemIndex++;
        });

        $(document).on('change', '.product-select', function () {
            const $row = $(this).closest('tr');
            const $selected = $(this).find('option:selected');

            if ($selected.val()) {
                const name = $selected.data('name') || '';
                const unit = $selected.data('unit') || 'pcs';
                const price = parseFloat($selected.data('price')) || 0;
                const stock = parseFloat($selected.data('stock')) || 0;

                $row.find('.product-name-input').val(name);
                $row.find('.unit-input').val(unit);
                $row.find('.price-input').val(price);
                $row.find('.stock-display').val(formatNumber(stock));
            } else {
                $row.find('.product-name-input').val('');
                $row.find('.stock-display').val('0,00');
            }

            calculateRowTotal($row);
        });

        $(document).on('input', '.qty-input, .price-input, .disc1-input, .disc2-input, .disc3-input, .disc4-input', function () {
            calculateRowTotal($(this).closest('tr'));
        });

        $(document).on('click', '.remove-item', function () {
            if ($('#items-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });
    });
</script>
@endpush