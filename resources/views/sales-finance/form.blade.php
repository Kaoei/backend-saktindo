@extends('layouts.dashboard', [
    'title' => 'Form Input SO',
    'pageTitle' => 'Sales Order (SO)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">Input SO</li>',
])

@push('styles')
<style>
    /* Custom Table Layout Dropdown for Product Selection */
    .product-select2-dropdown {
        min-width: 720px !important;
        max-width: 90vw !important;
        border-radius: 8px !important;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18) !important;
        border: 1px solid #cbd5e1 !important;
        overflow: hidden !important;
        z-index: 1060 !important;
    }

    .product-select2-dropdown .select2-search--dropdown {
        padding: 10px 14px;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .product-select2-dropdown .select2-search__field {
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 7px 12px !important;
        font-size: 0.875rem !important;
    }

    .product-select2-dropdown .select2-results__options {
        max-height: 340px !important;
        padding: 0 !important;
    }

    .product-dropdown-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f1f5f9;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        color: #475569;
        border-bottom: 2px solid #cbd5e1;
        padding: 9px 14px;
    }

    .product-opt-row {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px 4px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease-in-out;
    }

    .product-select2-dropdown .select2-results__option {
        padding: 0 10px !important;
        border-radius: 0 !important;
    }

    .product-select2-dropdown .select2-results__option[aria-selected="true"] {
        background-color: #e0f2fe !important;
    }

    .product-select2-dropdown .select2-results__option--highlighted {
        background-color: #eff6ff !important;
    }

    .product-select2-dropdown .select2-results__option--highlighted .text-dark,
    .product-select2-dropdown .select2-results__option--highlighted .text-secondary {
        color: #0f172a !important;
    }

    /* Col widths inside dropdown */
    .col-prod-name { flex: 0 0 42%; min-width: 0; }
    .col-prod-loc  { flex: 0 0 28%; min-width: 0; }
    .col-prod-stock { flex: 0 0 15%; text-align: right; }
    .col-prod-price { flex: 0 0 15%; text-align: right; }

    /* Single select rendered value */
    .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 38px;
        display: flex;
        align-items: center;
    }
</style>
@endpush

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

                    <!-- Pre-Order (Indent) Configuration -->
                    <div class="col-12 mb-3">
                        <div class="card border border-primary bg-light p-3 mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_pre_order" id="is_pre_order_switch" value="1" @checked(old('is_pre_order', $order->is_pre_order))>
                                    <label class="form-check-label fw-bold text-primary" for="is_pre_order_switch">
                                        <i class="feather icon-clock me-1"></i> Pesanan Pre-Order / Indent
                                    </label>
                                    <div class="text-muted small">Aktifkan opsi ini jika pesanan berupa barang inden/pre-order yang membutuhkan DP dan estimasi jadwal kedatangan.</div>
                                </div>
                            </div>
                            
                            <div id="pre_order_fields" class="row mt-3 g-3" style="{{ old('is_pre_order', $order->is_pre_order) ? '' : 'display: none;' }}">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Estimasi Tanggal Tiba (ETA)</label>
                                    <input type="date" name="pre_order_eta" class="form-control form-control-sm" value="{{ old('pre_order_eta', optional($order->pre_order_eta)->format('Y-m-d')) }}">
                                    <small class="text-muted">Target kedatangan barang dari supplier / pabrik.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Target Uang Muka / DP (Rp)</label>
                                    <input type="number" step="0.01" min="0" name="dp_amount" class="form-control form-control-sm" value="{{ old('dp_amount', $order->dp_amount ?? 0) }}" placeholder="Contoh: 5000000">
                                    <small class="text-muted">Nominal DP yang harus dibayar customer.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Catatan Pre-Order / Indent</label>
                                    <input type="text" name="pre_order_notes" class="form-control form-control-sm" value="{{ old('pre_order_notes', $order->pre_order_notes) }}" placeholder="Contoh: Indent pabrik 30 hari kerja">
                                    <small class="text-muted">Ketentuan khusus pre-order atau no. kontrak.</small>
                                </div>
                            </div>
                        </div>
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
                                <th style="width: 26%;">Nama Produk <span class="text-danger">*</span></th>
                                <th style="width: 6%;">Unit</th>
                                <th style="width: 16%;">Lokasi Gudang & Rak</th>
                                <th style="width: 8%;">Stok</th>
                                <th style="width: 7%;">Qty <span class="text-danger">*</span></th>
                                <th style="width: 12%;">Harga Unit (Rp) <span class="text-danger">*</span></th>
                                <th style="width: 13%;">Diskon (D1% + D2% + D3% + D4%)</th>
                                <th style="width: 9%;">Subtotal (Rp)</th>
                                <th style="width: 3%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                @php
                                    $selectedCode = $item['product_code'] ?? '';
                                    $selectedProduct = $productOptions->firstWhere('id', $selectedCode);
                                    $qty = (float) ($item['quantity'] ?? 1);
                                    $price = (float) ($item['unit_price'] ?? ($selectedProduct?->custom_price ?? $selectedProduct?->last_purchase_price ?? 0));
                                    $d1 = (float) ($item['discount_1'] ?? 0);
                                    $d2 = (float) ($item['discount_2'] ?? 0);
                                    $d3 = (float) ($item['discount_3'] ?? 0);
                                    $d4 = (float) ($item['discount_4'] ?? 0);
                                    $netPrice = $price * (1 - $d1/100) * (1 - $d2/100) * (1 - $d3/100) * (1 - $d4/100);
                                    $lineTotal = $qty * $netPrice;
                                @endphp
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $index }}][product_code]" class="form-select product-select no-select2" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach($productOptions as $product)
                                                <option value="{{ $product->id }}"
                                                    data-name="{{ $product->item_name }}"
                                                    data-sku="{{ $product->sku ?? '' }}"
                                                    data-part="{{ $product->part_number ?? '' }}"
                                                    data-unit="{{ $product->unit ?: 'pcs' }}"
                                                    data-price="{{ (float) ($product->custom_price ?? $product->last_purchase_price ?? 0) }}"
                                                    data-stock="{{ (float) ($product->available_stock ?? 0) }}"
                                                    data-location="{{ $product->warehouse_location ?? '-' }}"
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
                                        <input type="text" class="form-control location-display bg-light text-dark small" value="{{ $selectedProduct->warehouse_location ?? '-' }}" readonly style="font-size: 0.8rem;" title="Lokasi Penyimpanan di Gudang">
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

        function formatProductOption(data) {
            if (!data.id) {
                return $('<span class="text-muted">' + (data.text || '-- Pilih Produk --') + '</span>');
            }

            const $el = $(data.element);
            const name = $el.data('name') || data.text || '';
            const sku = $el.data('sku') || '';
            const partNumber = $el.data('part') || '';
            const unit = $el.data('unit') || 'pcs';
            const location = $el.data('location') || '-';
            const stock = parseFloat($el.data('stock')) || 0;
            const price = parseFloat($el.data('price')) || 0;

            let subMeta = [];
            if (sku) subMeta.push(`<span class="badge bg-light text-secondary border me-1"><i class="feather icon-tag me-1"></i>${sku}</span>`);
            if (partNumber) subMeta.push(`<span class="badge bg-light text-muted border me-1"><i class="feather icon-hash me-1"></i>${partNumber}</span>`);
            const metaHtml = subMeta.length > 0 ? `<div class="mt-1">${subMeta.join('')}</div>` : '';

            const stockBadge = stock > 0
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">${formatNumber(stock)} ${unit}</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">0 ${unit}</span>`;

            const priceFormatted = price > 0 ? 'Rp ' + formatNumber(price) : '<span class="text-muted">-</span>';

            return $(`
                <div class="product-opt-row">
                    <div class="col-prod-name pe-2">
                        <div class="fw-semibold text-dark text-truncate" title="${name}">${name}</div>
                        ${metaHtml}
                    </div>
                    <div class="col-prod-loc pe-2">
                        <div class="small text-truncate text-secondary" title="${location}">
                            <i class="feather icon-map-pin text-primary me-1"></i>${location}
                        </div>
                    </div>
                    <div class="col-prod-stock pe-2">
                        ${stockBadge}
                    </div>
                    <div class="col-prod-price">
                        <span class="fw-semibold text-dark small">${priceFormatted}</span>
                    </div>
                </div>
            `);
        }

        function formatProductSelection(data) {
            if (!data.id) {
                return data.text || '-- Pilih Produk --';
            }
            const $el = $(data.element);
            const name = $el.data('name') || data.text || '';
            const sku = $el.data('sku');
            return sku ? `${name} (${sku})` : name;
        }

        function matchProduct(params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }
            if (typeof data.text === 'undefined') {
                return null;
            }

            const term = params.term.toLowerCase();
            const $el = $(data.element);
            const name = String($el.data('name') || data.text || '').toLowerCase();
            const sku = String($el.data('sku') || '').toLowerCase();
            const part = String($el.data('part') || '').toLowerCase();
            const location = String($el.data('location') || '').toLowerCase();

            if (name.indexOf(term) > -1 || sku.indexOf(term) > -1 || part.indexOf(term) > -1 || location.indexOf(term) > -1) {
                return data;
            }

            return null;
        }

        function initProductSelect($elements) {
            $elements.each(function () {
                const $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownCssClass: 'product-select2-dropdown',
                    placeholder: '-- Pilih Produk --',
                    allowClear: false,
                    templateResult: formatProductOption,
                    templateSelection: formatProductSelection,
                    matcher: matchProduct
                });
            });
        }

        // Add sticky header on Select2 open
        $(document).on('select2:open', '.product-select', function() {
            const $dropdown = $('.product-select2-dropdown');
            if ($dropdown.find('.product-dropdown-header').length === 0) {
                const headerHtml = `
                    <div class="product-dropdown-header d-flex align-items-center">
                        <div class="col-prod-name pe-2">Nama Produk & SKU</div>
                        <div class="col-prod-loc pe-2">Lokasi Gudang & Rak</div>
                        <div class="col-prod-stock pe-2">Stok</div>
                        <div class="col-prod-price">Harga Satuan</div>
                    </div>
                `;
                $dropdown.find('.select2-results').prepend(headerHtml);
            }
        });

        // Initialize existing product selects
        initProductSelect($('.product-select'));

        function buildProductOptionsHtml(selectedId = '') {
            let options = '<option value="">-- Pilih Produk --</option>';
            productOptions.forEach(p => {
                const selected = p.id === selectedId ? 'selected' : '';
                const skuText = p.sku ? ` (${p.sku})` : '';
                const nameEsc = String(p.item_name || '').replace(/"/g, '&quot;');
                const skuEsc = String(p.sku || '').replace(/"/g, '&quot;');
                const partEsc = String(p.part_number || '').replace(/"/g, '&quot;');
                const locationEsc = String(p.warehouse_location || '-').replace(/"/g, '&quot;');
                const price = p.custom_price || p.last_purchase_price || 0;
                options += `<option value="${p.id}" data-name="${nameEsc}" data-sku="${skuEsc}" data-part="${partEsc}" data-unit="${p.unit || 'pcs'}" data-price="${price}" data-stock="${p.available_stock || 0}" data-location="${locationEsc}" ${selected}>${nameEsc}${skuText}</option>`;
            });
            return options;
        }

        $('#add-item').on('click', function () {
            const optionsHtml = buildProductOptionsHtml();
            const $newRow = $(`
                <tr class="item-row">
                    <td>
                        <select name="items[${itemIndex}][product_code]" class="form-select product-select no-select2" required>
                            ${optionsHtml}
                        </select>
                        <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name-input" value="">
                    </td>
                    <td>
                        <input type="text" name="items[${itemIndex}][unit]" class="form-control unit-input" value="pcs" required>
                    </td>
                    <td>
                        <input type="text" class="form-control location-display bg-light text-dark small" value="-" readonly style="font-size: 0.8rem;" title="Lokasi Penyimpanan di Gudang">
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
            `);
            $('#items-table tbody').append($newRow);
            initProductSelect($newRow.find('.product-select'));
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
                const location = $selected.data('location') || '-';

                $row.find('.product-name-input').val(name);
                $row.find('.unit-input').val(unit);
                $row.find('.price-input').val(price);
                $row.find('.stock-display').val(formatNumber(stock));
                $row.find('.location-display').val(location);
            } else {
                $row.find('.product-name-input').val('');
                $row.find('.stock-display').val('0,00');
                $row.find('.location-display').val('-');
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

        $('#is_pre_order_switch').on('change', function() {
            if ($(this).is(':checked')) {
                $('#pre_order_fields').slideDown(200);
            } else {
                $('#pre_order_fields').slideUp(200);
            }
        });
    });
</script>
@endpush