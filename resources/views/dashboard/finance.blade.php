@extends('layouts.dashboard', [
    'title' => 'Dashboard Finance',
    'pageTitle' => 'Dashboard Finance',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Dashboard Finance</li>',
])

@section('content')
<!-- Alert & Reminder Panel -->
<div class="row mb-4">
    <!-- Alert 1: Stok Minimum -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-light-danger text-danger h-100">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 text-danger fw-bold">Stok Minimum (&lt; 50)</h6>
                    <span class="h4 mb-0 fw-bold">{{ $lowStockProducts->count() }}</span> <span class="small">Produk</span>
                </div>
                <i class="feather icon-alert-triangle f-30"></i>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <button class="btn btn-sm btn-danger w-100" data-toggle="modal" data-target="#lowStockModal">Detail</button>
            </div>
        </div>
    </div>

    <!-- Alert 2: Transaksi > 30 Hari Belum Selesai -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-light-warning text-warning-dark h-100">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 text-warning-dark fw-bold">Transaksi &gt; 30 Hari</h6>
                    <span class="h4 mb-0 fw-bold">{{ $pendingOrders30Days->count() }}</span> <span class="small">Order</span>
                </div>
                <i class="feather icon-clock f-30"></i>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <button class="btn btn-sm btn-warning w-100 text-dark" data-toggle="modal" data-target="#pendingOrdersModal">Detail</button>
            </div>
        </div>
    </div>

    <!-- Alert 3: Piutang Jatuh Tempo (< 7 Hari) -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-light-primary text-primary h-100">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 text-primary fw-bold">Jatuh Tempo AR (7 Hari)</h6>
                    <span class="h4 mb-0 fw-bold">{{ $dueAR->count() }}</span> <span class="small">Invoice</span>
                </div>
                <i class="feather icon-dollar-sign f-30"></i>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <button class="btn btn-sm btn-primary w-100" data-toggle="modal" data-target="#dueARModal">Detail</button>
            </div>
        </div>
    </div>

    <!-- Alert 5: Barang Dipesan tapi Kosong/Kurang Stok -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-light-danger text-danger h-100">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 text-danger fw-bold">Dipesan &amp; Stok Kurang</h6>
                    <span class="h4 mb-0 fw-bold">{{ $orderedPendingStock->count() }}</span> <span class="small">Produk</span>
                </div>
                <i class="feather icon-shopping-cart f-30"></i>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <button class="btn btn-sm btn-danger w-100" data-toggle="modal" data-target="#orderedPendingStockModal">Detail</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Low Stock -->
<div class="modal fade" id="lowStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title">Detail Stok Minimum (&lt; 50)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Nama Produk</th><th>Rak</th><th class="text-end">Stok Saat Ini</th></tr></thead>
                    <tbody>
                        @foreach($lowStockProducts as $p)
                            <tr>
                                <td>{{ $p->supplierProduct->name ?? 'N/A' }}</td>
                                <td>{{ $p->rack->rak_kode ?? '-' }} ({{ strtoupper($p->rack->gudang ?? 'JS') }})</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($p->qty, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Pending Orders > 30 Days -->
<div class="modal fade" id="pendingOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi &gt; 30 Hari Belum Selesai</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>No. SO</th><th>Customer</th><th>Tanggal Order</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($pendingOrders30Days as $so)
                            <tr>
                                <td><a href="{{ route('sales-finance.show', $so) }}">{{ $so->id }}</a></td>
                                <td>{{ $so->customer_name }}</td>
                                <td>{{ $so->order_date->format('d/m/Y') }}</td>
                                <td><span class="badge bg-warning text-dark">{{ $so->order_status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Due AR -->
<div class="modal fade" id="dueARModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title">Detail Piutang Jatuh Tempo (&lt; 7 Hari)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>No. Invoice</th><th>Tgl Jatuh Tempo</th><th class="text-end">Sisa Piutang</th></tr></thead>
                    <tbody>
                        @foreach($dueAR as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</td>
                                <td class="text-end fw-bold text-danger">Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 5: Ordered Pending Stock -->
<div class="modal fade" id="orderedPendingStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title">Detail Barang Dipesan tapi Kosong / Kurang Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0" id="shortage-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" id="selectAllShortage" title="Pilih Semua">
                            </th>
                            <th>Nama Produk</th>
                            <th class="text-end">Total Dipesan</th>
                            <th class="text-end">Stok Gudang</th>
                            <th class="text-end">Kekurangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderedPendingStock as $item)
                            @php
                                $shortage = $item->total_ordered - $item->current_stock;
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="shortage-checkbox"
                                        data-product-code="{{ $item->product_code }}"
                                        data-product-name="{{ $item->product_name }}"
                                        data-qty="{{ $shortage }}"
                                        data-unit="{{ $item->unit }}"
                                        data-price="{{ $item->last_purchase_price }}">
                                </td>
                                <td>{{ $item->product_name ?? 'N/A' }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_ordered, 0) }} {{ $item->unit }}</td>
                                <td class="text-end">{{ number_format($item->current_stock, 0) }} {{ $item->unit }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($shortage, 0) }} {{ $item->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <small class="text-muted"><span id="selectedCount">0</span> barang dipilih</small>
                <button type="button" class="btn btn-primary" id="btnCreatePOFromShortage" disabled>
                    <i class="feather icon-truck mr-1"></i> Buat PO Supplier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for submitting shortage items to PO create -->
<form id="shortageToPoForm" action="{{ route('supplier-po.create-from-shortage') }}" method="POST" style="display: none;">
    @csrf
    <div id="shortageFormFields"></div>
</form>

@push('scripts')
<script>
$(document).ready(function() {
    // Select all toggle
    $('#selectAllShortage').on('change', function() {
        $('.shortage-checkbox').prop('checked', $(this).is(':checked'));
        updateShortageSelection();
    });

    // Individual checkbox change
    $(document).on('change', '.shortage-checkbox', function() {
        var total = $('.shortage-checkbox').length;
        var checked = $('.shortage-checkbox:checked').length;
        $('#selectAllShortage').prop('checked', total === checked);
        updateShortageSelection();
    });

    function updateShortageSelection() {
        var checked = $('.shortage-checkbox:checked').length;
        $('#selectedCount').text(checked);
        $('#btnCreatePOFromShortage').prop('disabled', checked === 0);
    }

    // Create PO from selected shortage items
    $('#btnCreatePOFromShortage').on('click', function() {
        var $checked = $('.shortage-checkbox:checked');
        if ($checked.length === 0) {
            alert('Pilih minimal 1 barang untuk dibuatkan PO.');
            return;
        }

        var $fields = $('#shortageFormFields');
        $fields.empty();

        $checked.each(function(i) {
            var $cb = $(this);
            var code = $cb.data('product-code');
            if (code === undefined || code === null) code = '';
            var name = $cb.data('product-name') || '';
            var qty = $cb.data('qty') || 1;
            var unit = $cb.data('unit') || 'pcs';
            var price = $cb.data('price') || 0;

            $fields.append('<input type="hidden" name="shortage_items[' + i + '][product_code]" value="' + code + '">');
            $fields.append('<input type="hidden" name="shortage_items[' + i + '][product_name]" value="' + name + '">');
            $fields.append('<input type="hidden" name="shortage_items[' + i + '][qty]" value="' + qty + '">');
            $fields.append('<input type="hidden" name="shortage_items[' + i + '][unit]" value="' + unit + '">');
            $fields.append('<input type="hidden" name="shortage_items[' + i + '][price]" value="' + price + '">');
        });

        $('#shortageToPoForm').submit();
    });
});
</script>
@endpush

<style>
    .bg-light-danger { background-color: #fde8e8 !important; }
    .bg-light-warning { background-color: #fef3c7 !important; }
    .bg-light-primary { background-color: #e0f2fe !important; }
    .bg-light-info { background-color: #e0f7fa !important; }
    .text-warning-dark { color: #b45309 !important; }
</style>

<div class="row">
    <!-- Card 3: Total Revenue -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Penjualan</span>
                        <h3 class="mb-0 fw-bold mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-3 bg-light-info text-info rounded-circle">
                        <i class="feather icon-dollar-sign f-24"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Outstanding AR -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Piutang (AR)</span>
                        <h3 class="mb-0 fw-bold mt-1">Rp {{ number_format($totalOutstandingAR, 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-3 bg-light-danger text-danger rounded-circle">
                        <i class="feather icon-credit-card f-24"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 6: Total Supplier POs -->
    <div class="col-xl-4 col-md-12 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Supplier PO Diajukan</span>
                    <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalSupplierPOs) }} <span class="h6 text-muted">po</span></h3>
                </div>
                <div class="p-3 bg-light-warning text-warning rounded-circle">
                    <i class="feather icon-truck f-24"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Invoices Table -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Invoice Terakhir</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end">Piutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ $inv->invoice_number }}</span>
                                        <br>
                                        <small class="text-muted">SO: {{ $inv->sales_order_id }}</small>
                                    </td>
                                    <td><span class="badge bg-light-info text-dark">{{ strtoupper($inv->tax_type) }}</span></td>
                                    <td>
                                        @if($inv->status === 'paid')
                                            <span class="badge bg-light-success text-success">Lunas</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data invoice terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
