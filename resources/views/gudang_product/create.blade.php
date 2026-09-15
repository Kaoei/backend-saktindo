@extends('layouts.dashboard', [
    'title' => 'Simpan Barang ke Rak',
    'pageTitle' => 'Produk Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('gudang-product.index').'">Produk Gudang</a></li><li class="breadcrumb-item">Simpan ke Rak</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-bold">Simpan Barang Masuk ke Rak</h5>
                    <small class="text-muted">Tempatkan barang yang masuk ke satu atau beberapa rak penyimpanan yang berbeda</small>
                </div>
                <a href="{{ route('gudang-product.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali ke Stok
                </a>
            </div>

            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal menyimpan data:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('gudang-product.store') }}" id="form-simpan-rak">
                    @csrf

                    <!-- 1. Pilih Barang Masuk -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            Pilih Barang Masuk (Pending / Partial) <span class="text-danger">*</span>
                        </label>

                        <select name="in_bound_id" id="inbound-select" class="form-select border-primary shadow-sm" required>
                            <option value="">-- Pilih Dokumen Barang Masuk --</option>
                            @foreach($inbounds as $inbound)
                                @php
                                    $existingRacks = [];
                                    if ($inbound->supplierProduct && $inbound->supplierProduct->gudangProducts) {
                                        foreach ($inbound->supplierProduct->gudangProducts as $gp) {
                                            if ($gp->qty > 0) {
                                                $existingRacks[] = ($gp->rack->rak_kode ?? $gp->rack_id ?? '-') . ' (' . $gp->qty . ' pcs)';
                                            }
                                        }
                                    }
                                    $existingRacksStr = count($existingRacks) > 0 ? implode(', ', $existingRacks) : 'Belum ada di rak';
                                @endphp
                                <option value="{{ $inbound->id }}"
                                    data-item-name="{{ $inbound->supplierProduct->item_name ?? '-' }}"
                                    data-sku="{{ $inbound->supplierProduct->sku ?? '-' }}"
                                    data-qty="{{ $inbound->qty_received }}"
                                    data-supplier="{{ $inbound->supplier->name ?? '-' }}"
                                    data-date="{{ $inbound->received_date }}"
                                    data-existing-racks="{{ $existingRacksStr }}"
                                    {{ old('in_bound_id', $selectedInbound) == $inbound->id ? 'selected' : '' }}>
                                    {{ $inbound->id }} - {{ $inbound->supplierProduct->item_name ?? '-' }} (Qty: {{ $inbound->qty_received }} pcs) - Supplier: {{ $inbound->supplier->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Inbound Details Card -->
                    <div id="inbound-info-card" class="card bg-light border-0 mb-4 p-3" style="display: none;">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nama Barang & SKU</small>
                                <strong id="info-item-name" class="text-dark fs-6">-</strong>
                                <span id="info-sku" class="badge bg-secondary ms-1">-</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Supplier</small>
                                <span id="info-supplier" class="fw-semibold text-dark">-</span>
                            </div>
                            <div class="col-md-2 text-md-center">
                                <small class="text-muted d-block">Total Diterima</small>
                                <span id="info-total-qty" class="badge bg-primary fs-6 px-3 py-2">0 pcs</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Lokasi Rak Saat Ini</small>
                                <small id="info-existing-racks" class="text-muted fw-semibold">-</small>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Alokasi ke Beberapa Rak Berbeda -->
                    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-sm">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 border-bottom pb-2 gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="feather icon-layers text-primary me-1"></i>
                                    Alokasi Rak Penyimpanan
                                </h6>
                                <small class="text-muted">Bisa membagi barang masuk ini ke satu atau lebih rak sekaligus</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge bg-light text-dark border px-3 py-2">
                                    Total Diterima: <strong id="badge-total-received">0</strong>
                                </div>
                                <div class="badge bg-light-primary text-primary border px-3 py-2">
                                    Total Dialokasikan: <strong id="badge-total-allocated">0</strong>
                                </div>
                                <div class="badge bg-light-warning text-warning border px-3 py-2" id="badge-remaining-container">
                                    Sisa: <strong id="badge-remaining">0</strong>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-rack">
                                    <i class="feather icon-plus me-1"></i> Tambah Rak Lain
                                </button>
                            </div>
                        </div>

                        <!-- Table Allocations -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="allocation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 22%;">Gudang <span class="text-danger">*</span></th>
                                        <th style="min-width: 45%;">Rak Penyimpanan <span class="text-danger">*</span></th>
                                        <th style="width: 23%;">Qty di Rak Ini <span class="text-danger">*</span></th>
                                        <th style="width: 10%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="allocation-body">
                                    <!-- Dynamic rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>

                        <div id="allocation-alert" class="alert alert-warning mt-3 mb-0 py-2 d-none" role="alert">
                            <i class="feather icon-alert-triangle me-1"></i>
                            <span id="allocation-alert-text">Total kuantitas yang dialokasikan tidak sesuai dengan total barang masuk.</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btn-submit">
                            <i class="feather icon-check-circle me-1"></i> Simpan ke Rak
                        </button>

                        <a href="{{ route('gudang-product.index') }}" class="btn btn-outline-secondary">
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
    const racksData = @json($racks);
    const $inboundSelect = $('#inbound-select');
    const $inboundInfoCard = $('#inbound-info-card');
    const $infoItemName = $('#info-item-name');
    const $infoSku = $('#info-sku');
    const $infoSupplier = $('#info-supplier');
    const $infoTotalQty = $('#info-total-qty');
    const $infoExistingRacks = $('#info-existing-racks');

    const $badgeTotalReceived = $('#badge-total-received');
    const $badgeTotalAllocated = $('#badge-total-allocated');
    const $badgeRemaining = $('#badge-remaining');
    const $badgeRemainingContainer = $('#badge-remaining-container');
    const $allocationBody = $('#allocation-body');
    const $btnAddRack = $('#btn-add-rack');
    const $allocationAlert = $('#allocation-alert');
    const $allocationAlertText = $('#allocation-alert-text');
    const $btnSubmit = $('#btn-submit');

    let rowIndex = 0;
    let currentTotalQty = 0;

    function buildRackOptions(selectedGudang = '', selectedRack = '') {
        let html = '<option value="">-- Pilih Rak --</option>';
        racksData.forEach(r => {
            const gudangLabel = r.gudang ? r.gudang.toUpperCase() : '';
            const isSelected = (selectedRack && String(r.rak_kode) === String(selectedRack)) ? 'selected' : '';
            const locationText = r.location ? ` - ${r.location}` : '';
            const gudangBadge = gudangLabel ? ` [${gudangLabel}]` : '';
            
            html += `<option value="${r.rak_kode}" data-gudang="${gudangLabel}" ${isSelected}>
                ${r.rak_kode}${gudangBadge}${locationText}
            </option>`;
        });
        return html;
    }

    function addAllocationRow(gudangType = 'JS', rackId = '', qty = '') {
        const index = rowIndex++;
        const rackOptionsHtml = buildRackOptions(gudangType, rackId);

        const trHtml = `
            <tr class="allocation-row" data-index="${index}">
                <td>
                    <select name="allocations[${index}][gudang_type]" class="form-select form-select-sm gudang-select" required>
                        <option value="JS" ${gudangType === 'JS' ? 'selected' : ''}>Gudang JS</option>
                        <option value="SJB" ${gudangType === 'SJB' ? 'selected' : ''}>Gudang SJB</option>
                    </select>
                </td>
                <td>
                    <select name="allocations[${index}][rack_id]" class="form-select form-select-sm rack-select" required>
                        ${rackOptionsHtml}
                    </select>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" name="allocations[${index}][qty]" class="form-control form-control-sm qty-input text-end" min="1" value="${qty}" required placeholder="0">
                        <span class="input-group-text">pcs</span>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 remove-row-btn" title="Hapus Rak">
                        <i class="feather icon-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;

        const $tr = $(trHtml);
        $allocationBody.append($tr);

        // Auto-match gudang if user selects rack that has explicit gudang
        $tr.find('.rack-select').on('change', function() {
            const rackGudang = $(this).find('option:selected').attr('data-gudang');
            if (rackGudang && (rackGudang === 'JS' || rackGudang === 'SJB')) {
                $tr.find('.gudang-select').val(rackGudang);
            }
            recalculateAllocations();
        });

        $tr.find('.qty-input').on('input change', function() {
            recalculateAllocations();
        });

        $tr.find('.remove-row-btn').on('click', function() {
            if ($allocationBody.find('.allocation-row').length > 1) {
                $tr.remove();
                recalculateAllocations();
            } else {
                alert('Minimal harus ada 1 alokasi rak.');
            }
        });

        recalculateAllocations();
    }

    function recalculateAllocations() {
        let totalAllocated = 0;
        $allocationBody.find('.qty-input').each(function() {
            const val = parseInt($(this).val()) || 0;
            totalAllocated += val;
        });

        const remaining = currentTotalQty - totalAllocated;

        $badgeTotalReceived.text(currentTotalQty + ' pcs');
        $badgeTotalAllocated.text(totalAllocated + ' pcs');
        $badgeRemaining.text(remaining + ' pcs');

        if (currentTotalQty <= 0) {
            $allocationAlert.addClass('d-none');
            $btnSubmit.prop('disabled', false);
            return;
        }

        if (totalAllocated > currentTotalQty) {
            $allocationAlert.removeClass('d-none').removeClass('alert-warning alert-info').addClass('alert-danger');
            $allocationAlertText.text(`Total kuantitas dialokasikan (${totalAllocated} pcs) melebihi kuantitas yang diterima (${currentTotalQty} pcs)!`);
            $badgeRemainingContainer.removeClass('bg-light-warning text-warning bg-light-success text-success').addClass('bg-light-danger text-danger');
            $btnSubmit.prop('disabled', true);
        } else if (remaining > 0) {
            $allocationAlert.removeClass('d-none').removeClass('alert-danger alert-info').addClass('alert-warning');
            $allocationAlertText.text(`Masih ada sisa ${remaining} pcs yang belum dialokasikan ke rak. Sisa ini akan tetap pending untuk ditempatkan kemudian.`);
            $badgeRemainingContainer.removeClass('bg-light-danger text-danger bg-light-success text-success').addClass('bg-light-warning text-warning');
            $btnSubmit.prop('disabled', false);
        } else {
            $allocationAlert.addClass('d-none');
            $badgeRemainingContainer.removeClass('bg-light-warning text-warning bg-light-danger text-danger').addClass('bg-light-success text-success');
            $btnSubmit.prop('disabled', false);
        }
    }

    $inboundSelect.on('change', function() {
        const $selected = $(this).find('option:selected');
        const val = $(this).val();

        if (!val) {
            $inboundInfoCard.slideUp(200);
            currentTotalQty = 0;
            $allocationBody.empty();
            recalculateAllocations();
            return;
        }

        const itemName = $selected.attr('data-item-name') || '-';
        const sku = $selected.attr('data-sku') || '-';
        const qty = parseInt($selected.attr('data-qty')) || 0;
        const supplier = $selected.attr('data-supplier') || '-';
        const existingRacks = $selected.attr('data-existing-racks') || '-';

        $infoItemName.text(itemName);
        $infoSku.text('SKU: ' + sku);
        $infoSupplier.text(supplier);
        $infoTotalQty.text(qty + ' pcs');
        $infoExistingRacks.text(existingRacks);

        currentTotalQty = qty;
        $inboundInfoCard.slideDown(200);

        // Reset and initialize 1 allocation row with full qty
        $allocationBody.empty();
        rowIndex = 0;
        addAllocationRow('JS', '', qty > 0 ? qty : 1);
    });

    $btnAddRack.on('click', function() {
        let totalAllocated = 0;
        $allocationBody.find('.qty-input').each(function() {
            totalAllocated += parseInt($(this).val()) || 0;
        });
        const remaining = Math.max(0, currentTotalQty - totalAllocated);
        addAllocationRow('JS', '', remaining > 0 ? remaining : 1);
    });

    // If preselected on page load
    if ($inboundSelect.val()) {
        $inboundSelect.trigger('change');
    }
});
</script>
@endpush