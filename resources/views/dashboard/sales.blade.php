@extends('layouts.dashboard', [
    'title' => 'Dashboard Sales',
    'pageTitle' => 'Dashboard Sales',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Dashboard Sales</li>',
])

@section('content')
<!-- Alert & Reminder Panel -->
<div class="row mb-4">
    <!-- Alert 1: Stok Minimum -->
    <div class="col-xl-4 col-md-6 mb-3">
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
    <div class="col-xl-4 col-md-6 mb-3">
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

    <!-- Alert 5: Barang Dipesan tapi Kosong/Kurang Stok -->
    <div class="col-xl-4 col-md-6 mb-3">
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
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th class="text-end">Total Dipesan</th>
                            <th class="text-end">Stok Gudang Saat Ini</th>
                            <th class="text-end">Kekurangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderedPendingStock as $item)
                            @php
                                $shortage = $item->total_ordered - $item->current_stock;
                            @endphp
                            <tr>
                                <td>{{ $item->product_name ?? 'N/A' }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_ordered, 0) }} {{ $item->unit }}</td>
                                <td class="text-end">{{ number_format($item->current_stock, 0) }} {{ $item->unit }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($shortage, 0) }} {{ $item->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-danger { background-color: #fde8e8 !important; }
    .bg-light-warning { background-color: #fef3c7 !important; }
    .bg-light-primary { background-color: #e0f2fe !important; }
    .bg-light-info { background-color: #e0f7fa !important; }
    .text-warning-dark { color: #b45309 !important; }
</style>

<div class="row">
    <!-- Card 1: Total Customers -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Customer</span>
                        <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalCustomers) }}</h3>
                    </div>
                    <div class="p-3 bg-light-primary text-primary rounded-circle">
                        <i class="feather icon-users f-24"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Orders -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Sales Order</span>
                        <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalOrders) }}</h3>
                    </div>
                    <div class="p-3 bg-light-success text-success rounded-circle">
                        <i class="feather icon-shopping-cart f-24"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Total Stock Qty -->
    <div class="col-xl-4 col-md-12 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Total Stok Fisik Gudang</span>
                    <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalPhysicalProducts) }} <span class="h6 text-muted">unit</span></h3>
                </div>
                <div class="p-3 bg-light-secondary text-secondary rounded-circle">
                    <i class="feather icon-package f-24"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@include('dashboard.partials.unpaid-clients-card')

<div class="row">
    <!-- Recent Orders Table -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Pesanan Terakhir (Sales Order)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. SO</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('sales-finance.show', $order) }}" class="fw-bold">{{ $order->id }}</a></td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td>
                                        <span class="badge bg-light-primary text-primary">{{ str_replace('_', ' ', ucfirst($order->order_status)) }}</span>
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data order terbaru.</td>
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
