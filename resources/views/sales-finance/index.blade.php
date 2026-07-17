@extends('layouts.dashboard', [
    'title' => 'Sales & Finance',
    'pageTitle' => 'Sales & Finance Flow',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Sales & Finance</li>',
])

@section('content')
@php
    $outstanding = $orders->sum(fn ($order) => (float) ($order->invoice?->outstanding_amount ?? 0));
@endphp

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sales</h5>
                <small class="text-muted">Alur pemilihan PI, pengumpulan invoice bulanan, dan proses SJS.</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Langkah 1</div>
                            <h6 class="mb-1">Pilih PI</h6>
                            <p class="mb-0 text-muted small">Proses dimulai dari pemilihan PI (Purchase Invoice/Purchase Item) sebagai dasar transaksi Sales.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Langkah 2</div>
                            <h6 class="mb-1">Ikuti Alur Surat Jalan</h6>
                            <p class="mb-0 text-muted small">Sistem menyerupai alur pembuatan Surat Jalan agar proses pengiriman tetap konsisten.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Langkah 3</div>
                            <h6 class="mb-1">Kumpulkan Invoice</h6>
                            <p class="mb-0 text-muted small">Invoice dikumpulkan terlebih dahulu selama periode berjalan sampai genap 1 bulan.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Langkah 4</div>
                            <h6 class="mb-1">Gabung Invoice via SJS</h6>
                            <p class="mb-0 text-muted small">Setelah 1 bulan, seluruh invoice digabung menjadi 1 invoice menggunakan proses SJS.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Total Sales Order</div><h3 class="mb-0">{{ $orders->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Pending Stock</div><h3 class="mb-0">{{ $orders->where('stock_status', 'pending')->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Invoice Outstanding</div><h3 class="mb-0">{{ $orders->filter(fn ($order) => $order->invoice?->status === 'outstanding')->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Outstanding</div><h5 class="mb-0">Rp {{ number_format($outstanding, 0, ',', '.') }}</h5></div></div></div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Follow Up Sales</h5>
                    <small class="text-muted">PI, Sales Order, stok, invoice bulanan, Surat Jalan, proses SJS, dan pembayaran.</small>
                </div>
                @if(auth()->user()?->hasPermission('sales_finance.create'))
                    <a href="{{ route('sales-finance.create') }}" class="btn btn-primary">
                        <i class="material-icons-two-tone text-white">add_shopping_cart</i>
                        Input PI
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Sales Order</th>
                                <th>Customer</th>
                                <th>PI / PO</th>
                                <th>Status Order</th>
                                <th>Stok</th>
                                <th>Invoice</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="align-middle"><span class="badge bg-light-secondary font-monospace">{{ $order->id }}</span></td>
                                    <td class="align-middle">{{ $order->customer_name }}</td>
                                    <td class="align-middle">
                                        {{ $order->customer_po_number }}
                                        <div class="small text-muted">{{ optional($order->po_date)->format('d M Y') ?: '-' }}</div>
                                    </td>
                                    <td class="align-middle"><span class="badge bg-light-primary">{{ str_replace('_', ' ', ucfirst($order->order_status)) }}</span></td>
                                    <td class="align-middle">
                                        <span class="badge {{ $order->stock_status === 'pending' ? 'bg-light-warning' : ($order->stock_status === 'available' ? 'bg-light-success' : 'bg-light-secondary') }}">
                                            {{ str_replace('_', ' ', ucfirst($order->stock_status)) }}
                                        </span>
                                        @if($order->warehouse_task_reference)
                                            <div class="small text-muted">{{ $order->warehouse_task_reference }}</div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        @if($order->invoice)
                                            <a href="{{ route('sales-finance.show', $order) }}">{{ $order->invoice->invoice_number }}</a>
                                            <div class="small text-muted">{{ ucfirst($order->invoice->status) }}</div>
                                            @if($order->invoice->warehouseTask)
                                                <div class="small text-muted">Task: {{ $order->invoice->warehouseTask->id }} / {{ ucfirst($order->invoice->warehouseTask->status) }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-end">Rp {{ number_format((float) $order->grand_total, 0, ',', '.') }}</td>
                                    <td class="align-middle text-end">
                                        <a href="{{ route('sales-finance.show', $order) }}" class="text-primary" title="Detail"><i class="feather icon-eye f-16 text-primary"></i></a>
                                        @if(auth()->user()?->hasPermission('sales_finance.edit'))
                                            <a href="{{ route('sales-finance.edit', $order) }}" class="text-success ms-2" title="Edit"><i class="feather icon-edit f-16 text-success"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">Belum ada data Sales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
