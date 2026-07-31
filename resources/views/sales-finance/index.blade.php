@extends('layouts.dashboard', [
    'title' => 'Sales & Finance',
    'pageTitle' => 'Sales & Finance Flow',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Sales & Finance</li>',
])

@section('content')
@php
    $invoices = $orders->flatMap->invoices->unique('id');
    $outstanding = $invoices->sum(fn ($invoice) => (float) $invoice->outstanding_amount);
@endphp

<div class="row mt-3">
    <div class="col-12">
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Total Sales Order</div><h3 class="mb-0">{{ $orders->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Pending Stock</div><h3 class="mb-0">{{ $orders->where('stock_status', 'pending')->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Invoice Outstanding</div><h3 class="mb-0">{{ $invoices->where('status', 'outstanding')->count() }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><div class="text-muted small">Outstanding</div><h5 class="mb-0">Rp {{ number_format($outstanding, 0, ',', '.') }}</h5></div></div></div>
</div>

@if(auth()->user()?->hasPermission('sales_finance.create'))
    <form method="POST" action="{{ route('sales-finance.invoice.consolidate') }}" class="card">
        @csrf
        <div class="card-header">
            <h5 class="mb-0">Invoice Gabungan Bulanan</h5>
            <small class="text-muted">Gabungkan beberapa Sales Order customer yang sama dalam satu periode.</small>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Pilih customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->nama_customer }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3"><label class="form-label">Periode Awal</label><input type="date" name="period_start" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                <div class="col-md-2 mb-3"><label class="form-label">Periode Akhir</label><input type="date" name="period_end" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}" required></div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Jenis Faktur</label>
                    <select name="tax_type" class="form-select" required>
                        <option value="js">JS</option>
                        <option value="sjb_non_pajak">SJB Non Pajak</option>
                        <option value="sjb_pajak">SJB Pajak</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3"><label class="form-label">Tanggal Invoice</label><input type="date" name="invoice_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-1 mb-3 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Buat</button></div>
            </div>
            <input type="date" name="due_date" class="form-control d-none">
        </div>
    </form>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Follow Up Sales</h5>
                </div>
                @if(auth()->user()?->hasPermission('sales_finance.create'))
                    <a href="{{ route('sales-finance.create') }}" class="btn btn-primary">
                        <i class="material-icons-two-tone text-white">add_shopping_cart</i>
                        Input SO
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
                                <th>PO</th>
                                <th>Status Order</th>
                                <th>Stok</th>
                                <th>Invoice</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php($invoice = $order->invoices->first())
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
                                        @if($invoice)
                                            <a href="{{ route('sales-finance.show', $order) }}">{{ $invoice->invoice_number }}</a>
                                            <div class="small text-muted">{{ ucfirst($invoice->invoice_type) }} / {{ ucfirst($invoice->status) }}</div>
                                            @if($invoice->warehouseTask)
                                                <div class="small text-muted">Task: {{ $invoice->warehouseTask->id }} / {{ ucfirst($invoice->warehouseTask->status) }}</div>
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
