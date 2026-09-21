@extends('layouts.dashboard', [
    'title' => 'Edit Promo Bundling',
    'pageTitle' => 'Edit Promo Bundling',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('bundle-promos.index').'">Promo Bundling</a></li><li class="breadcrumb-item">Edit</li>',
])

@section('content')

<form action="{{ route('bundle-promos.update', $bundlePromo->id) }}" method="POST" id="bundle-form" class="mt-3">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="feather icon-alert-octagon me-1"></i> Terdapat beberapa kesalahan pengisian form:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Detail Promo -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">Informasi Paket Promo</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Promo <span class="text-danger">*</span></label>
                            <input type="text" name="bundle_code" class="form-control" value="{{ old('bundle_code', $bundlePromo->bundle_code) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Paket Promo <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $bundlePromo->name) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keterangan / Syarat Ketentuan</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $bundlePromo->description) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Mulai (Opsional)</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($bundlePromo->start_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Berakhir (Opsional)</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($bundlePromo->end_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $bundlePromo->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status', $bundlePromo->status) === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Komponen Barang -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">Komponen Produk dalam Paket</h5>
                        <small class="text-muted">Pilih produk dan kuantitas yang termasuk dalam 1 paket bundling.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">
                        <i class="feather icon-plus me-1"></i> Tambah Produk
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 250px;">PRODUK <span class="text-danger">*</span></th>
                                    <th style="width: 110px;" class="text-center">QTY / PAKET <span class="text-danger">*</span></th>
                                    <th style="width: 150px;" class="text-end">HARGA SATUAN (RP) <span class="text-danger">*</span></th>
                                    <th style="width: 150px;" class="text-end">SUBTOTAL (RP)</th>
                                    <th style="width: 50px;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @foreach($bundlePromo->items as $idx => $item)
                                    <tr class="item-row" data-index="{{ $idx }}">
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm product-select" required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" data-price="{{ (float) $p->last_purchase_price }}" data-sku="{{ $p->sku }}"
                                                        {{ $item->supplier_product_id == $p->id ? 'selected' : '' }}>
                                                        {{ $p->item_name }} {{ $p->sku ? "({$p->sku})" : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <button type="button" class="btn btn-outline-secondary btn-qty-minus">-</button>
                                                <input type="number" name="items[{{ $idx }}][qty]" class="form-control text-center qty-input" min="1" value="{{ $item->qty }}" required>
                                                <button type="button" class="btn btn-outline-secondary btn-qty-plus">+</button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm text-end price-input" min="0" step="100" value="{{ $item->unit_price }}" required>
                                        </td>
                                        <td class="text-end fw-bold text-dark subtotal-display">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1 remove-row-btn" title="Hapus">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary & Kalkulasi Diskon -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">Skema Diskon & Harga Paket</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Diskon Paket <span class="text-danger">*</span></label>
                        <select name="discount_type" id="discount_type" class="form-select" required>
                            <option value="fixed" {{ old('discount_type', $bundlePromo->discount_type) === 'fixed' ? 'selected' : '' }}>Potongan Nominal Tetap (Rp)</option>
                            <option value="percentage" {{ old('discount_type', $bundlePromo->discount_type) === 'percentage' ? 'selected' : '' }}>Diskon Persentase (%)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Nilai Diskon / Potongan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text {{ $bundlePromo->discount_type === 'percentage' ? 'd-none' : '' }}" id="discount_prefix">Rp</span>
                            <input type="number" name="discount_value" id="discount_value" class="form-control text-end fw-bold" min="0" step="1" value="{{ old('discount_value', $bundlePromo->discount_value) }}" required>
                            <span class="input-group-text {{ $bundlePromo->discount_type === 'percentage' ? '' : 'd-none' }}" id="discount_suffix">%</span>
                        </div>
                        <small class="text-muted">Potongan harga khusus bundling dari total harga normal.</small>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Harga Normal:</span>
                        <span class="fw-semibold" id="display-original-price">Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Total Potongan Promo:</span>
                        <span class="fw-semibold" id="display-discount-amount">- Rp 0</span>
                    </div>

                    <div class="p-3 bg-light-success rounded border border-success-subtle mt-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="d-block text-success fw-bold">Harga Promo Paket:</span>
                                <small class="text-muted" id="display-savings-pct">Hemat 0%</small>
                            </div>
                            <h4 class="mb-0 text-success fw-bold" id="display-bundle-price">Rp 0</h4>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-save me-1"></i> Perbarui Paket Promo
                        </button>
                        <a href="{{ route('bundle-promos.index') }}" class="btn btn-light">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
$(function() {
    let rowIndex = {{ $bundlePromo->items->count() }};
    const productsData = @json($products);

    function calculateBundle() {
        let originalTotal = 0;

        $('#items-body .item-row').each(function() {
            const qty = parseInt($(this).find('.qty-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            const subtotal = qty * price;
            originalTotal += subtotal;

            $(this).find('.subtotal-display').text('Rp ' + new Intl.NumberFormat('id-ID').format(subtotal));
        });

        const discType = $('#discount_type').val();
        const discVal = parseFloat($('#discount_value').val()) || 0;

        let discountAmount = 0;
        if (discType === 'percentage') {
            discountAmount = (originalTotal * discVal) / 100;
        } else {
            discountAmount = discVal;
        }

        if (discountAmount > originalTotal) {
            discountAmount = originalTotal;
        }

        const bundlePrice = Math.max(0, originalTotal - discountAmount);
        const savingsPct = originalTotal > 0 ? ((discountAmount / originalTotal) * 100).toFixed(1) : 0;

        const fmt = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

        $('#display-original-price').text(fmt(originalTotal));
        $('#display-discount-amount').text('- ' + fmt(discountAmount));
        $('#display-bundle-price').text(fmt(bundlePrice));
        $('#display-savings-pct').text('Hemat ' + savingsPct + '%');
    }

    // Toggle discount type prefix/suffix
    $('#discount_type').on('change', function() {
        if ($(this).val() === 'percentage') {
            $('#discount_prefix').addClass('d-none');
            $('#discount_suffix').removeClass('d-none');
            $('#discount_value').attr('max', 100);
        } else {
            $('#discount_prefix').removeClass('d-none');
            $('#discount_suffix').addClass('d-none');
            $('#discount_value').removeAttr('max');
        }
        calculateBundle();
    });

    $('#discount_value').on('input change', calculateBundle);

    // Product select change -> set default unit price
    $(document).on('change', '.product-select', function() {
        const $opt = $(this).find('option:selected');
        const price = parseFloat($opt.data('price')) || 0;
        const $row = $(this).closest('.item-row');
        $row.find('.price-input').val(price);
        calculateBundle();
    });

    $(document).on('input change', '.qty-input, .price-input', calculateBundle);

    // Stepper buttons (+ / -)
    $(document).on('click', '.btn-qty-minus', function() {
        const $input = $(this).siblings('.qty-input');
        const currentVal = parseInt($input.val()) || 1;
        if (currentVal > 1) {
            $input.val(currentVal - 1).trigger('change');
        }
    });

    $(document).on('click', '.btn-qty-plus', function() {
        const $input = $(this).siblings('.qty-input');
        const currentVal = parseInt($input.val()) || 0;
        $input.val(currentVal + 1).trigger('change');
    });

    // Remove row
    $(document).on('click', '.remove-row-btn', function() {
        if ($('#items-body .item-row').length > 1) {
            $(this).closest('.item-row').remove();
            calculateBundle();
        } else {
            alert('Minimal harus ada 1 produk dalam paket promo.');
        }
    });

    // Add item
    $('#btn-add-item').on('click', function() {
        let optHtml = '<option value="">-- Pilih Produk --</option>';
        productsData.forEach(p => {
            const skuText = p.sku ? ` (${p.sku})` : '';
            const price = parseFloat(p.last_purchase_price) || 0;
            optHtml += `<option value="${p.id}" data-price="${price}" data-sku="${p.sku || ''}">${p.item_name}${skuText}</option>`;
        });

        const rowHtml = `
            <tr class="item-row" data-index="${rowIndex}">
                <td>
                    <select name="items[${rowIndex}][product_id]" class="form-select form-select-sm product-select" required>
                        ${optHtml}
                    </select>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-qty-minus">-</button>
                        <input type="number" name="items[${rowIndex}][qty]" class="form-control text-center qty-input" min="1" value="1" required>
                        <button type="button" class="btn btn-outline-secondary btn-qty-plus">+</button>
                    </div>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][unit_price]" class="form-control form-control-sm text-end price-input" min="0" step="100" value="0" required>
                </td>
                <td class="text-end fw-bold text-dark subtotal-display">
                    Rp 0
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 remove-row-btn" title="Hapus">
                        <i class="feather icon-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#items-body').append(rowHtml);
        rowIndex++;
        calculateBundle();
    });

    calculateBundle();
});
</script>
@endpush
