@extends('layouts.dashboard', [
    'title' => 'Form Sales Order',
    'pageTitle' => 'Form Sales Order',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">Form</li>',
])

@section('content')
@php
    $items = old('items', $order->exists ? $order->items->map(fn ($item) => [
        'product_code' => $item->product_code,
        'product_name' => $item->product_name,
        'unit' => $item->unit,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
        'discount' => $item->discount ?? 0,
    ])->toArray() : [['product_code' => '', 'product_name' => '', 'unit' => 'pcs', 'quantity' => 1, 'unit_price' => 0, 'discount' => 0]]);
@endphp

@push('styles')
<style>
    #items-table th:first-child,
    #items-table td:first-child {
        width: 30%;
    }

    #items-table th:nth-child(2),
    #items-table td:nth-child(2) {
        width: 10%;
    }

    #items-table th:nth-child(3),
    #items-table td:nth-child(3) {
        width: 12%;
    }

    #items-table th:nth-child(4),
    #items-table td:nth-child(4) {
        width: 16%;
    }

    #items-table th:nth-child(5),
    #items-table td:nth-child(5) {
        width: 12%;
    }

    #items-table th:nth-child(6),
    #items-table td:nth-child(6) {
        width: 20%;
    }
</style>
@endpush

<div class="row">
    <div class="col-12">
        <form method="POST" action="{{ $action }}" class="card">
            @csrf
            @if($method !== 'POST') @method($method) @endif
            <div class="card-header">
                <h5 class="mb-0">Input PO Customer</h5>
                <small class="text-muted">Data ini menjadi dasar Sales Order, pengecekan stok, invoice, dan surat jalan.</small>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">Periksa kembali data yang wajib diisi.</div>
                @endif

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer Terdaftar</label>
                        <select name="customer_id" id="customer-select2" class="form-select no-select2">
                            @if(old('customer_id', $order->customer_id))
                                @php
                                    $selCust = $customers->firstWhere('id', old('customer_id', $order->customer_id));
                                @endphp
                                @if($selCust)
                                    <option value="{{ $selCust->id }}" selected>{{ $selCust->nama_customer }} ({{ $selCust->id }})</option>
                                @endif
                            @else
                                <option value="">Manual / belum terdaftar</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama Customer</label>
                        <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name', $order->customer_name) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nomor PO Customer</label>
                        <input type="text" name="customer_po_number" class="form-control @error('customer_po_number') is-invalid @enderror" value="{{ old('customer_po_number', $order->customer_po_number) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal PO</label>
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
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pilih Toko <span class="text-danger">*</span></label>
                        <select name="toko" class="form-select" required>
                            <option value="js" @selected(old('toko', $order->toko) === 'js')>JS</option>
                            <option value="sjb" @selected(old('toko', $order->toko) === 'sjb')>SJB</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Jenis Invoice <span class="text-danger">*</span></label>
                        <select name="jenis_invoice" class="form-select" required>
                            <option value="normal" @selected(old('jenis_invoice', $order->jenis_invoice) === 'normal')>Invoice Normal</option>
                            <option value="gabungan" @selected(old('jenis_invoice', $order->jenis_invoice) === 'gabungan')>Invoice Gabungan</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 mb-2">
                    <h6 class="mb-0 fw-semibold">Detail Item Pesanan</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="items-table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Unit</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Harga</th>
                                <th class="text-center">Diskon (%)</th>
                                <th class="text-center" style="width: 170px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][product_code]" class="form-select product-select2" required>
                                            @if($item['product_code'])
                                                @php
                                                    $selectedProduct = $productOptions->firstWhere('id', $item['product_code']);
                                                    $selectedLabel = $selectedProduct
                                                        ? $selectedProduct->item_name.($selectedProduct->sku ? ' - '.$selectedProduct->sku : '')
                                                        : ($item['product_name'] ?? '');
                                                @endphp
                                                <option value="{{ $item['product_code'] }}" selected>{{ $selectedLabel }}</option>
                                            @else
                                                <option value=""></option>
                                            @endif
                                        </select>
                                        <div class="product-picker-info small text-success mt-1">
                                            Harga: <strong>Rp {{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}</strong>
                                        </div>
                                    </td>
                                    <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item['unit'] ?? 'pcs' }}" required></td>
                                    <td><input type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" class="form-control text-center" value="{{ $item['quantity'] ?? 1 }}" required></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" class="form-control text-end" value="{{ $item['unit_price'] ?? 0 }}" required></td>
                                    <td><input type="number" step="0.1" min="0" max="100" name="items[{{ $index }}][discount]" class="form-control text-center" value="{{ $item['discount'] ?? 0 }}"></td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button type="button" class="btn btn-sm btn-primary add-item-btn me-1" title="Tambah Item Baru"><i class="material-icons-two-tone text-white" style="font-size: 16px; vertical-align: middle;">add</i> Tambah Item</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus Item"><i class="material-icons-two-tone" style="font-size: 16px; vertical-align: middle;">delete</i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end my-3">
                    <div class="col-md-6 col-lg-4">
                        <div class="card bg-light border-0 shadow-sm p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold text-dark" id="summary-subtotal">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Total Diskon:</span>
                                <span class="fw-semibold" id="summary-discount">- Rp 0</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center fw-bold">
                                <span class="fs-6 text-dark">Total Belanja:</span>
                                <span class="fs-5 text-primary" id="summary-grandtotal">Rp 0</span>
                            </div>
                        </div>
                    </div>
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

        function calculateOrderSummary() {
            let totalSubtotal = 0;
            let totalDiscountAmount = 0;

            $('#items-table tbody tr').each(function () {
                const qty = parseFloat($(this).find('input[name$="[quantity]"]').val()) || 0;
                const price = parseFloat($(this).find('input[name$="[unit_price]"]').val()) || 0;
                const discount = parseFloat($(this).find('input[name$="[discount]"]').val()) || 0;

                const lineSubtotal = qty * price;
                const lineDiscount = lineSubtotal * (discount / 100);

                totalSubtotal += lineSubtotal;
                totalDiscountAmount += lineDiscount;
            });

            const grandTotal = totalSubtotal - totalDiscountAmount;

            $('#summary-subtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(totalSubtotal));
            $('#summary-discount').text('- Rp ' + new Intl.NumberFormat('id-ID').format(totalDiscountAmount));
            $('#summary-grandtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal));
        }

        $(document).on('input change', 'input[name$="[quantity]"], input[name$="[unit_price]"], input[name$="[discount]"]', function () {
            calculateOrderSummary();
        });

        function initProductSelect2(element) {
            $(element).select2({
                theme: 'bootstrap-5',
                ajax: {
                    url: '{{ route("api.search.products") }}',
                    dataType: 'json',
                    delay: 150,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                placeholder: 'Pilih / Cari produk...',
                minimumInputLength: 0,
                allowClear: true,
                templateResult: function (data) {
                    if (!data.id) return data.text;
                    const stock = data.stock !== undefined ? data.stock : 0;
                    const rack = data.rack || '-';
                    const location = data.location || '-';
                    const price = data.price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.price) : 'Rp 0';
                    const stockClass = stock > 0 ? 'bg-success' : 'bg-secondary';

                    return $(`
                        <div class="py-1">
                            <div class="fw-semibold text-dark">${data.text}</div>
                            <div class="small text-muted mt-1 d-flex align-items-center flex-wrap gap-2">
                                <span class="badge ${stockClass}">Stok: ${stock} ${data.unit || 'pcs'}</span>
                                <span class="badge bg-light text-dark border">Rak: ${rack}</span>
                                <span class="badge bg-light text-dark border">Gudang: ${location}</span>
                                <span class="text-primary fw-medium">Harga: ${price}</span>
                            </div>
                        </div>
                    `);
                },
                templateSelection: function (data) {
                    if (!data.id) return data.text;
                    return data.text + (data.stock !== undefined ? ` [Stok: ${data.stock} ${data.unit || 'pcs'} | Rak: ${data.rack || '-'} | Gudang: ${data.location || '-'}]` : '');
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                const $row = $(this).closest('tr');
                $row.find('input[name$="[unit]"]').val(data.unit || 'pcs');

                // Price/discount & Stock/Rak/Gudang details
                const $info = $row.find('.product-picker-info');
                const normalPrice = Number(data.price);
                const discountPercent = Number(data.discount_percent || 0);
                const formattedNormal = 'Rp ' + new Intl.NumberFormat('id-ID').format(normalPrice);
                const stockText = data.stock !== undefined ? `Stok: <strong>${data.stock} ${data.unit || 'pcs'}</strong>` : '';
                const rackText = data.rack ? ` | Rak: <strong>${data.rack}</strong>` : '';
                const locationText = data.location ? ` | Gudang: <strong>${data.location}</strong>` : '';

                if (discountPercent > 0) {
                    $row.find('input[name$="[discount]"]').val(discountPercent);
                } else if (!$row.find('input[name$="[discount]"]').val()) {
                    $row.find('input[name$="[discount]"]').val(0);
                }

                $info.html(`${stockText}${rackText}${locationText} | Harga: <strong>${formattedNormal}</strong>`).show();
                $row.find('input[name$="[unit_price]"]').val(normalPrice.toFixed(2));
                calculateOrderSummary();
            }).on('select2:clear', function(e) {
                const $row = $(this).closest('tr');
                $row.find('.product-picker-info').html('').hide();
                $row.find('input[name$="[unit_price]"]').val(0);
                $row.find('input[name$="[discount]"]').val(0);
                calculateOrderSummary();
            });
        }

        // Initialize Select2 on existing products
        initProductSelect2('.product-select2');

        // Add item click handler (Support both button triggers)
        $(document).on('click', '.add-item-btn, #add-item', function () {
            const newRow = $(`
                <tr>
                    <td>
                        <select name="items[${itemIndex}][product_code]" class="form-select product-select2" required>
                            <option value=""></option>
                        </select>
                        <div class="product-picker-info small text-success mt-1" style="display: none;"></div>
                    </td>
                    <td><input type="text" name="items[${itemIndex}][unit]" class="form-control" value="pcs" required></td>
                    <td><input type="number" step="0.01" min="0.01" name="items[${itemIndex}][quantity]" class="form-control text-center" value="1" required></td>
                    <td><input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_price]" class="form-control text-end" value="0" required></td>
                    <td><input type="number" step="0.1" min="0" max="100" name="items[${itemIndex}][discount]" class="form-control text-center" value="0"></td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button type="button" class="btn btn-sm btn-primary add-item-btn me-1" title="Tambah Item Baru"><i class="material-icons-two-tone text-white" style="font-size: 16px; vertical-align: middle;">add</i> Tambah Item</button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus Item"><i class="material-icons-two-tone" style="font-size: 16px; vertical-align: middle;">delete</i></button>
                    </td>
                </tr>
            `);
            $('#items-table tbody').append(newRow);
            initProductSelect2(newRow.find('.product-select2'));
            itemIndex++;
            calculateOrderSummary();
        });

        $(document).on('click', '.remove-item', function () {
            if ($('#items-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateOrderSummary();
            }
        });

        // Customer Select2 AJAX
        $('#customer-select2').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: '{{ route("api.search.customers") }}',
                dataType: 'json',
                delay: 150,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            placeholder: 'Pilih / Cari customer...',
            minimumInputLength: 0,
            allowClear: true
        }).on('select2:select', function(e) {
            const data = e.params.data;
            // Auto fill Customer Name
            $('input[name="customer_name"]').val(data.text.replace(/\s\(MCS-\d+\)$/, ''));
        });

        // Initial summary calculation on load
        calculateOrderSummary();
    });
</script>
@endpush
