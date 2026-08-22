@extends('layouts.dashboard', [
    'title' => 'Tambah Barang Masuk',
    'pageTitle' => 'Barang Masuk',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('inbound.index').'">Barang Masuk</a></li><li class="breadcrumb-item">Tambah Barang Masuk</li>'
])

@push('styles')
<style>
    /* Force Select2 dropdown to open downwards */
    .select2-dropdown {
        top: 100% !important;
        bottom: auto !important;
    }
</style>
@endpush

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-semibold">Tambah Barang Masuk</h5>
                    <small class="text-muted">Pencatatan & Penerimaan Barang dari Supplier</small>
                </div>

                <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>
            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong class="d-block mb-1">Gagal menyimpan data:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('inbound.store') }}" id="inbound-form">
                    @csrf

                    <!-- Selection Header -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-bold text-primary mb-1">
                                        <i class="material-icons-two-tone text-primary me-1">receipt_long</i>
                                        Pilih Supplier PO (Opsional)
                                    </label>
                                    <select name="supplier_po_id" id="po-select" class="form-select border-primary shadow-sm">
                                        <option value="">-- Tanpa Template PO --</option>
                                        @foreach($supplierPos as $po)
                                            <option value="{{ $po->id }}">
                                                {{ $po->po_number }} - {{ $po->supplier->name ?? 'Supplier' }} ({{ count($po->items) }} Item - Total Rp {{ number_format($po->total_amount, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-1">
                                        Supplier <span class="text-danger">*</span>
                                    </label>
                                    <select name="supplier_id" id="supplier-select" class="form-select bg-white" required>
                                        <option value="">Pilih Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Nomor Invoice (Input Manual)
                            </label>
                            <input type="text"
                                   name="invoice_number"
                                   class="form-control"
                                   placeholder="Contoh: INV-2026/07/001"
                                   value="{{ old('invoice_number') }}">
                            <small class="text-muted">Nomor Invoice pengiriman dari supplier jika ada.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Tanggal Masuk / Terima <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   name="received_date"
                                   class="form-control"
                                   value="{{ old('received_date', date('Y-m-d')) }}"
                                   required>
                        </div>
                    </div>

                    <!-- Container for Checklist Multi-Item PO Table (Only visible when PO selected) -->
                    <div id="po-items-container" style="display: none;" class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">
                                    <i class="material-icons-two-tone text-primary me-1">fact_check</i>
                                    Daftar Barang Supplier PO
                                </h6>
                                <small class="text-muted">Centang barang yang diterima dan tentukan kondisi masing-masing produk.</small>
                            </div>
                            <span class="badge bg-primary fs-6" id="po-items-count">0 Item Dichecklist</span>
                        </div>

                        <div class="table-responsive border rounded bg-white mb-3 shadow-sm">
                            <table class="table table-hover align-middle mb-0" id="po-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="check-all-items" class="form-check-input" checked title="Centang Semua">
                                        </th>
                                        <th style="min-width: 220px;">Barang Dipesan / SKU</th>
                                        <th class="text-center" style="width: 90px;">Dipesan</th>
                                        <th style="min-width: 260px;">Status Kondisi Penerimaan</th>
                                        <th style="width: 110px;">Qty Baik</th>
                                        <th style="width: 110px;">Qty Reject</th>
                                        <th style="width: 110px;">Qty Kurang</th>
                                        <th style="width: 130px;">HPP (Rp)</th>
                                        <th style="min-width: 160px;">Catatan Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody id="po-items-body">
                                    <!-- Dynamic checklist rows from JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Container for Multi-Item Manual Input (Only visible when NO PO selected) -->
                    <div id="manual-items-container" class="card border-0 bg-light p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">
                                    <i class="material-icons-two-tone text-primary me-1">edit_note</i>
                                    Detail Barang Masuk
                                </h6>
                                <small class="text-muted">Masukkan 1 atau lebih produk barang yang diterima dari supplier.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="add-manual-row">
                                <i class="feather icon-plus me-1"></i>
                                Tambah Baris Barang
                            </button>
                        </div>

                        <div class="table-responsive border rounded bg-white mb-2 shadow-sm">
                            <table class="table table-hover align-middle mb-0" id="manual-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 260px;">Produk Supplier <span class="text-danger">*</span></th>
                                        <th style="width: 120px;">Qty Baik <span class="text-danger">*</span></th>
                                        <th style="width: 120px;">Qty Reject</th>
                                        <th style="width: 120px;">Qty Kurang</th>
                                        <th style="width: 140px;">HPP Unit (Rp)</th>
                                        <th style="min-width: 180px;">Catatan Kondisi</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="manual-items-body">
                                    <!-- Dynamic manual rows from JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="feather icon-check-circle me-1"></i>
                            Simpan Barang Masuk
                        </button>

                        <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary">
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
    $(document).ready(function() {
        const supplierPosData = @json($supplierPos);
        const supplierProductsData = @json($supplierProducts);

        const $poSelect = $('#po-select');
        const $supplierSelect = $('#supplier-select');
        const $poItemsContainer = $('#po-items-container');
        const $poItemsBody = $('#po-items-body');
        const $poItemsCount = $('#po-items-count');
        const $checkAllItems = $('#check-all-items');
        const $manualItemsContainer = $('#manual-items-container');
        const $manualItemsBody = $('#manual-items-body');
        const $addManualRowBtn = $('#add-manual-row');
        let manualRowIndex = 0;

        // Force Select2 dropdown to open downwards (below)
        $(document).on('select2:open', function(e) {
            $('.select2-dropdown').removeClass('select2-dropdown--above').addClass('select2-dropdown--below');
        });

        function addManualRow(data = {}) {
            let optionsHtml = '<option value="">Pilih Produk</option>';
            supplierProductsData.forEach(p => {
                const selected = (data.supplier_product_id && String(p.id) === String(data.supplier_product_id)) ? 'selected' : '';
                const skuText = p.sku ? ` (${p.sku})` : '';
                const nameEsc = String(p.item_name || '').replace(/"/g, '&quot;');
                optionsHtml += `<option value="${p.id}" data-price="${p.last_purchase_price || 0}" ${selected}>${nameEsc}${skuText}</option>`;
            });

            const index = manualRowIndex++;
            const trHtml = `
                <tr class="manual-row">
                    <td>
                        <select name="items[${index}][supplier_product_id]" class="form-select form-select-sm manual-product-select" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${index}][qty_received]" class="form-control form-control-sm qty-received-input" min="0" value="${data.qty_received || 1}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${index}][qty_damaged]" class="form-control form-control-sm" min="0" value="${data.qty_damaged || 0}">
                    </td>
                    <td>
                        <input type="number" name="items[${index}][qty_missing]" class="form-control form-control-sm" min="0" value="${data.qty_missing || 0}">
                    </td>
                    <td>
                        <input type="number" name="items[${index}][hpp]" class="form-control form-control-sm manual-hpp-input" min="0" step="0.01" value="${data.hpp || 0}">
                    </td>
                    <td>
                        <input type="text" name="items[${index}][notes]" class="form-control form-control-sm" placeholder="Catatan kondisi..." value="${data.notes || ''}">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger p-1 remove-manual-row" title="Hapus Baris">
                            <i class="feather icon-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            const $tr = $(trHtml);

            $tr.find('.manual-product-select').on('change', function() {
                const price = $(this).find('option:selected').attr('data-price') || 0;
                $tr.find('.manual-hpp-input').val(price);
            });

            $tr.find('.remove-manual-row').on('click', function() {
                if ($manualItemsBody.find('.manual-row').length > 1) {
                    $tr.remove();
                } else {
                    $tr.find('input').val('');
                    $tr.find('.qty-received-input').val(1);
                    $tr.find('.manual-product-select').val('').trigger('change');
                }
            });

            $manualItemsBody.append($tr);
        }

        $addManualRowBtn.on('click', function() {
            addManualRow();
        });

        function toggleMode() {
            const selectedPoId = $poSelect.val();

            if (selectedPoId) {
                const selectedPo = supplierPosData.find(p => String(p.id) === String(selectedPoId));

                if (selectedPo) {
                    if (selectedPo.supplier_id) {
                        $supplierSelect.val(selectedPo.supplier_id).trigger('change.select2');
                    }

                    $manualItemsContainer.addClass('d-none').hide();
                    $poItemsContainer.removeClass('d-none').show();
                    renderPoItems(selectedPo.items || []);
                    return;
                }
            }

            $poItemsContainer.addClass('d-none').hide();
            $manualItemsContainer.removeClass('d-none').show();
            $poItemsBody.empty();

            if ($manualItemsBody.find('.manual-row').length === 0) {
                addManualRow();
            }
        }

        function updateCheckCount() {
            const checkedCount = $poItemsBody.find('.item-checkbox:checked').length;
            $poItemsCount.text(checkedCount + ' Item Dichecklist');
        }

        function renderPoItems(items) {
            $poItemsBody.empty();

            if (items.length === 0) {
                $poItemsBody.html(`<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada barang dalam PO ini.</td></tr>`);
                updateCheckCount();
                return;
            }

            items.forEach((item, index) => {
                const productName = item.supplier_product ? item.supplier_product.item_name : 'Produk Master';
                const productSku = item.supplier_product ? item.supplier_product.sku : '-';
                const price = parseFloat(item.price) || 0;
                const d1 = parseFloat(item.discount_1) || parseFloat(item.discount) || 0;
                const d2 = parseFloat(item.discount_2) || 0;
                const d3 = parseFloat(item.discount_3) || 0;
                const d4 = parseFloat(item.discount_4) || 0;
                const netHpp = (price * (1 - d1 / 100) * (1 - d2 / 100) * (1 - d3 / 100) * (1 - d4 / 100)).toFixed(2);
                const orderedQty = parseInt(item.qty) || 1;

                const trHtml = `
                    <tr class="item-row">
                        <td class="text-center align-middle">
                            <input type="checkbox" name="items[${index}][is_checked]" value="1" class="form-check-input item-checkbox" checked>
                            <input type="hidden" name="items[${index}][supplier_product_id]" value="${item.supplier_product_id}">
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${productName}</div>
                            <small class="text-muted">SKU: <code>${productSku}</code></small>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge bg-secondary px-2 py-1 fs-6">${orderedQty}</span>
                        </td>
                        <td class="align-middle">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <input type="radio" class="btn-check condition-radio" name="condition_${index}" id="cond_good_${index}" value="good" checked>
                                <label class="btn btn-outline-success" for="cond_good_${index}">
                                    🟢 Diterima Baik
                                </label>

                                <input type="radio" class="btn-check condition-radio" name="condition_${index}" id="cond_reject_${index}" value="reject">
                                <label class="btn btn-outline-warning text-dark" for="cond_reject_${index}">
                                    🟡 Ada Reject
                                </label>

                                <input type="radio" class="btn-check condition-radio" name="condition_${index}" id="cond_missing_${index}" value="missing">
                                <label class="btn btn-outline-danger" for="cond_missing_${index}">
                                    🔴 Ada Kurang
                                </label>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="items[${index}][qty_received]" class="form-control form-control-sm qty-received-input" min="0" value="${orderedQty}" required>
                        </td>
                        <td>
                            <input type="number" name="items[${index}][qty_damaged]" class="form-control form-control-sm qty-damaged-input" min="0" value="0">
                        </td>
                        <td>
                            <input type="number" name="items[${index}][qty_missing]" class="form-control form-control-sm qty-missing-input" min="0" value="0">
                        </td>
                        <td>
                            <input type="number" name="items[${index}][hpp]" class="form-control form-control-sm" min="0" step="0.01" value="${netHpp}">
                        </td>
                        <td>
                            <input type="text" name="items[${index}][notes]" class="form-control form-control-sm notes-input" placeholder="Catatan kondisi...">
                        </td>
                    </tr>
                `;

                const $tr = $(trHtml);

                const $checkbox = $tr.find('.item-checkbox');
                const $qtyReceivedInput = $tr.find('.qty-received-input');
                const $qtyDamagedInput = $tr.find('.qty-damaged-input');
                const $qtyMissingInput = $tr.find('.qty-missing-input');
                const $notesInput = $tr.find('.notes-input');
                const $radioGood = $tr.find(`#cond_good_${index}`);
                const $radioReject = $tr.find(`#cond_reject_${index}`);
                const $radioMissing = $tr.find(`#cond_missing_${index}`);

                function applyCondition() {
                    if ($radioGood.is(':checked')) {
                        $qtyReceivedInput.val(orderedQty);
                        $qtyDamagedInput.val(0);
                        $qtyMissingInput.val(0);
                    } else if ($radioReject.is(':checked')) {
                        if (orderedQty > 1) {
                            $qtyReceivedInput.val(orderedQty - 1);
                            $qtyDamagedInput.val(1);
                        } else {
                            $qtyReceivedInput.val(0);
                            $qtyDamagedInput.val(1);
                        }
                        $qtyMissingInput.val(0);
                        if (!$notesInput.val()) $notesInput.attr('placeholder', 'Detail barang reject/rusak...');
                        $qtyDamagedInput.focus();
                    } else if ($radioMissing.is(':checked')) {
                        if (orderedQty > 1) {
                            $qtyReceivedInput.val(orderedQty - 1);
                            $qtyMissingInput.val(1);
                        } else {
                            $qtyReceivedInput.val(0);
                            $qtyMissingInput.val(1);
                        }
                        $qtyDamagedInput.val(0);
                        if (!$notesInput.val()) $notesInput.attr('placeholder', 'Detail barang kurang/tidak ada...');
                        $qtyMissingInput.focus();
                    }
                }

                $radioGood.on('change', applyCondition);
                $radioReject.on('change', applyCondition);
                $radioMissing.on('change', applyCondition);

                $checkbox.on('change', function() {
                    updateCheckCount();
                    if (!this.checked) {
                        $tr.addClass('table-secondary text-muted');
                    } else {
                        $tr.removeClass('table-secondary text-muted');
                    }
                });

                $poItemsBody.append($tr);
            });

            updateCheckCount();
        }

        $checkAllItems.on('change', function() {
            const isChecked = this.checked;
            $poItemsBody.find('.item-checkbox').each(function() {
                this.checked = isChecked;
                const $tr = $(this).closest('tr');
                if (!isChecked) {
                    $tr.addClass('table-secondary text-muted');
                } else {
                    $tr.removeClass('table-secondary text-muted');
                }
            });
            updateCheckCount();
        });

        $poSelect.on('change select2:select', toggleMode);

        toggleMode();
    });
</script>
@endpush