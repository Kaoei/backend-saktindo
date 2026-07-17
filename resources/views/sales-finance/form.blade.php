@extends('layouts.dashboard', [
    'title' => 'Form Sales',
    'pageTitle' => 'Form Sales',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">Form</li>',
])

@section('content')
@php
    $productOptionPayload = $productOptions->map(fn ($product) => [
        'id' => $product->id,
        'label' => $product->item_name.($product->sku ? ' - '.$product->sku : ''),
        'name' => $product->item_name,
        'unit' => $product->unit ?: 'pcs',
        'price' => (float) $product->last_purchase_price,
        'stock' => (float) ($product->available_stock ?? 0),
    ])->values();
    $items = old('items', $order->exists ? $order->items->map(fn ($item) => [
        'product_code' => $item->product_code,
        'product_name' => $item->product_name,
        'unit' => $item->unit,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
    ])->toArray() : [['product_code' => '', 'product_name' => '', 'unit' => 'pcs', 'quantity' => 1, 'unit_price' => 0]]);
@endphp

@push('styles')
<style>
    .product-picker {
        position: relative;
        min-width: 260px;
    }

    .product-picker-menu {
        display: none;
        position: fixed;
        z-index: 2000;
        max-height: 320px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }

    .product-picker-menu.open {
        display: block;
    }

    .product-picker-option,
    .product-picker-empty {
        display: block;
        width: 100%;
        padding: 8px 12px;
        border: 0;
        background: transparent;
        text-align: left;
        font-size: 14px;
        line-height: 1.4;
    }

    .product-picker-option:hover,
    .product-picker-option:focus {
        background: #f3f6f9;
    }

    .product-picker-empty {
        color: #6c757d;
    }

    #items-table th:first-child,
    #items-table td:first-child {
        width: 34%;
    }

    #items-table th:nth-child(2),
    #items-table td:nth-child(2) {
        width: 14%;
    }

    #items-table th:nth-child(3),
    #items-table td:nth-child(3) {
        width: 14%;
    }

    #items-table th:nth-child(4),
    #items-table td:nth-child(4),
    #items-table th:nth-child(5),
    #items-table td:nth-child(5) {
        width: 16%;
    }
</style>
@endpush

<div class="row">
    <div class="col-12">
        <form method="POST" action="{{ $action }}" class="card">
            @csrf
            @if($method !== 'POST') @method($method) @endif
            <div class="card-header">
                <h5 class="mb-0">Input PI Customer</h5>
                <small class="text-muted">Data ini menjadi dasar Sales, pengecekan stok, pengumpulan invoice bulanan, Surat Jalan, dan proses SJS.</small>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">Periksa kembali data yang wajib diisi.</div>
                @endif

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer Terdaftar</label>
                        <select name="customer_id" class="form-select">
                            <option value="">Manual / belum terdaftar</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $order->customer_id) === $customer->id)>{{ $customer->nama_customer }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama Customer</label>
                        <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name', $order->customer_name) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nomor PI / PO Customer</label>
                        <input type="text" name="customer_po_number" class="form-control @error('customer_po_number') is-invalid @enderror" value="{{ old('customer_po_number', $order->customer_po_number) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal PI / PO</label>
                        <input type="date" name="po_date" class="form-control" value="{{ old('po_date', optional($order->po_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal Order</label>
                        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status Order</label>
                        <select name="order_status" class="form-select">
                            @foreach(['draft', 'stock_check', 'ready_to_invoice', 'pending_stock', 'invoiced', 'delivered', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(old('order_status', $order->order_status ?: 'draft') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-2 mb-2">
                    <div>
                        <h6 class="mb-0">Detail Item Pesanan</h6>
                        <small class="text-muted">Barang diambil dari stok Gudang yang sudah tersimpan di rak.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-item">
                        <i class="material-icons-two-tone">add</i>
                        Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Unit</th>
                                <th>Stok Barang</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                @php
                                    $selectedProductId = $productOptions->first(function ($product) use ($item) {
                                        return ($item['product_code'] ?? null) === $product->id
                                            || ($item['product_name'] ?? null) === $product->item_name
                                            || ($item['product_code'] ?? null) === $product->sku
                                            || ($item['product_code'] ?? null) === $product->part_number;
                                    })?->id;
                                @endphp
                                <tr>
                                    <td>
                                        @php
                                            $selectedProduct = $productOptions->firstWhere('id', $selectedProductId);
                                            $selectedLabel = $selectedProduct
                                                ? $selectedProduct->item_name.($selectedProduct->sku ? ' - '.$selectedProduct->sku : '')
                                                : '';
                                        @endphp
                                        <div class="product-picker">
                                            <input type="hidden" name="items[{{ $index }}][product_code]" class="product-code-input" value="{{ $selectedProductId }}" required>
                                            <input type="text" class="form-control product-search-input" value="{{ $selectedLabel }}" placeholder="Cari produk..." autocomplete="off" required>
                                            <div class="product-picker-menu"></div>
                                        </div>
                                    </td>
                                    <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item['unit'] ?? 'pcs' }}" required></td>
                                    <td>
                                        <input type="text" class="form-control stock-display" value="{{ $selectedProduct ? number_format((float) ($selectedProduct->available_stock ?? 0), 2, ',', '.') : '0,00' }}" readonly>
                                    </td>
                                    <td><input type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" required></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" class="form-control" value="{{ $item['unit_price'] ?? 0 }}" required></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="material-icons-two-tone">delete</i></button></td>
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
        const productOptions = @json($productOptionPayload);

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char];
            });
        }

        function applyProductOption($picker, selected) {
            const $row = $picker.closest('tr');

            if (!selected) {
                return;
            }

            $picker.find('.product-code-input').val(selected.id);
            $picker.find('.product-search-input').val(selected.label);
            closeProductMenus();
            $row.find('input[name$="[unit]"]').val(selected.unit || 'pcs');

            if (Number(selected.price) > 0) {
                $row.find('input[name$="[unit_price]"]').val(selected.price);
            }

            $row.find('.stock-display').val(formatStock(selected.stock));
        }

        function formatStock(value) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function renderProductMenu($picker, keyword = '') {
            const normalizedKeyword = keyword.toLowerCase().trim();
            const matches = productOptions
                .filter((product) => product.label.toLowerCase().includes(normalizedKeyword))
                .slice(0, 25);
            const $menu = menuForPicker($picker);

            if (!matches.length) {
                $menu.html('<div class="product-picker-empty">Produk tidak ditemukan</div>');
                return;
            }

            $menu.html(matches.map((product) => `
                <button type="button" class="product-picker-option" data-product-id="${escapeHtml(product.id)}">
                    <span class="d-block">${escapeHtml(product.label)}</span>
                    <span class="text-muted small">Stok: ${escapeHtml(formatStock(product.stock))} ${escapeHtml(product.unit || '')}</span>
                </button>
            `).join(''));
        }

        function positionProductMenu($picker) {
            const input = $picker.find('.product-search-input')[0];
            const menu = menuForPicker($picker)[0];
            const rect = input.getBoundingClientRect();
            const availableBelow = window.innerHeight - rect.bottom - 12;
            const availableAbove = rect.top - 12;
            const menuHeight = Math.min(320, Math.max(180, availableBelow > 180 ? availableBelow : availableAbove));
            const top = availableBelow > 180 ? rect.bottom + 4 : Math.max(12, rect.top - menuHeight - 4);

            $(menu).css({
                top: `${top}px`,
                left: `${rect.left}px`,
                width: `${Math.max(rect.width, 360)}px`,
                maxHeight: `${menuHeight}px`,
            });
        }

        function openProductMenu($picker, keyword = '') {
            closeProductMenus();
            const $menu = menuForPicker($picker);

            if (!$menu.parent().is('body')) {
                $menu.appendTo('body');
            }

            $picker.data('menu', $menu);
            $menu.data('picker', $picker);
            renderProductMenu($picker, keyword);
            positionProductMenu($picker);
            $menu.addClass('open');
        }

        function closeProductMenus() {
            $('.product-picker-menu.open').removeClass('open');
        }

        function menuForPicker($picker) {
            return $picker.data('menu') || $picker.find('.product-picker-menu');
        }

        function productPickerTemplate(index) {
            return `
                <div class="product-picker">
                    <input type="hidden" name="items[${index}][product_code]" class="product-code-input" required>
                    <input type="text" class="form-control product-search-input" placeholder="Cari produk..." autocomplete="off" required>
                    <div class="product-picker-menu"></div>
                </div>
            `;
        }

        $('#add-item').on('click', function () {
            $('#items-table tbody').append(`
                <tr>
                    <td>${productPickerTemplate(itemIndex)}</td>
                    <td><input type="text" name="items[${itemIndex}][unit]" class="form-control" value="pcs" required></td>
                    <td><input type="text" class="form-control stock-display" value="0,00" readonly></td>
                    <td><input type="number" step="0.01" min="0.01" name="items[${itemIndex}][quantity]" class="form-control" value="1" required></td>
                    <td><input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_price]" class="form-control" value="0" required></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="material-icons-two-tone">delete</i></button></td>
                </tr>
            `);
            itemIndex++;
        });

        $(document).on('click', '.remove-item', function () {
            if ($('#items-table tbody tr').length > 1) {
                const $picker = $(this).closest('tr').find('.product-picker');
                menuForPicker($picker).remove();
                $(this).closest('tr').remove();
            }
        });

        $(document).on('focus', '.product-search-input', function () {
            const $picker = $(this).closest('.product-picker');
            openProductMenu($picker, this.value);
        });

        $(document).on('input', '.product-search-input', function () {
            const $picker = $(this).closest('.product-picker');
            $picker.find('.product-code-input').val('');
            openProductMenu($picker, this.value);
        });

        $(document).on('click', '.product-picker-option', function () {
            const selected = productOptions.find((product) => product.id === $(this).data('product-id'));
            applyProductOption($(this).closest('.product-picker-menu').data('picker'), selected);
        });

        $(document).on('mousedown', function (event) {
            if (!$(event.target).closest('.product-picker, .product-picker-menu').length) {
                closeProductMenus();
            }
        });

        $(window).on('scroll resize', function () {
            const $menu = $('.product-picker-menu.open');
            if ($menu.length) {
                positionProductMenu($menu.data('picker'));
            }
        });

        $('form').on('submit', function (event) {
            const hasEmptyProduct = $('.product-code-input').filter(function () {
                return !this.value;
            }).length > 0;

            if (hasEmptyProduct) {
                event.preventDefault();
                alert('Pilih produk dari hasil pencarian terlebih dahulu.');
            }
        });
    });
</script>
@endpush
