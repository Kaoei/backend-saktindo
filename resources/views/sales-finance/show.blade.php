@extends('layouts.dashboard', [
    'title' => 'Detail Sales',
    'pageTitle' => 'Detail Sales',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">'.$order->id.'</li>',
])

@section('content')
@php
    $invoice = $order->invoice;
    $deliveryNote = $invoice?->deliveryNote;
    $warehouseTask = $invoice?->warehouseTask;
    $warehouseTaskReference = $warehouseTask?->id ?? $order->warehouse_task_reference ?? '-';
@endphp

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mt-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">{{ $order->id }}</h5>
                    <small class="text-muted">{{ $order->customer_name }} - PI/PO {{ $order->customer_po_number }}</small>
                </div>
                @if(auth()->user()?->hasPermission('sales_finance.edit'))
                    <a href="{{ route('sales-finance.edit', $order) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3"><div class="text-muted small">Status Order</div><span class="badge bg-light-primary">{{ str_replace('_', ' ', ucfirst($order->order_status)) }}</span></div>
                    <div class="col-md-3"><div class="text-muted small">Status Stok</div><span class="badge bg-light-secondary">{{ ucfirst($order->stock_status) }}</span></div>
                    <div class="col-md-3"><div class="text-muted small">Tanggal Order</div>{{ optional($order->order_date)->format('d M Y') }}</div>
                    <div class="col-md-3">
                        <div class="text-muted small">Task Gudang</div>
                        {{ $warehouseTaskReference }}
                        @if($warehouseTask)
                            <div><span class="badge bg-light-secondary">{{ ucfirst($warehouseTask->status) }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Stok</th>
                                <th>Status</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->product_name }}
                                        <div class="small text-muted">{{ $item->product_code ?: '-' }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $item->quantity, 2, ',', '.') }} {{ $item->unit }}</td>
                                    <td class="text-end">{{ number_format((float) $item->available_stock, 2, ',', '.') }}</td>
                                    <td><span class="badge {{ $item->stock_status === 'pending' ? 'bg-light-warning' : ($item->stock_status === 'available' ? 'bg-light-success' : 'bg-light-secondary') }}">{{ ucfirst($item->stock_status) }}</span></td>
                                    <td class="text-end">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end">Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</th></tr>
                            <tr><th colspan="5" class="text-end">Pajak</th><th class="text-end">Rp {{ number_format((float) $order->tax_amount, 0, ',', '.') }}</th></tr>
                            <tr><th colspan="5" class="text-end">Grand Total</th><th class="text-end">Rp {{ number_format((float) $order->grand_total, 0, ',', '.') }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($invoice)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Management</h5>
                    <small class="text-muted">{{ $invoice->invoice_number }} - Faktur {{ $invoice->faktur_number }} - dikumpulkan untuk invoice bulanan SJS</small>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><div class="text-muted small">Status</div><span class="badge bg-light-info">{{ ucfirst($invoice->status) }}</span></div>
                        <div class="col-md-3"><div class="text-muted small">Tanggal</div>{{ optional($invoice->invoice_date)->format('d M Y') }}</div>
                        <div class="col-md-3"><div class="text-muted small">Terbayar</div>Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</div>
                        <div class="col-md-3"><div class="text-muted small">Outstanding</div>Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</div>
                    </div>

                    <h6>Riwayat Pembayaran</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>No.</th><th>Tanggal</th><th>Metode</th><th>Rekening</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_number }}</td>
                                        <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                        <td>{{ str_replace('_', ' ', strtoupper($payment->method)) }}</td>
                                        <td>{{ strtoupper($payment->receiving_account) }}</td>
                                        <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada pembayaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if(auth()->user()?->hasPermission('sales_finance.edit'))
            <form method="POST" action="{{ route('sales-finance.stock-check', $order) }}" class="card">
                @csrf
                <div class="card-header"><h5 class="mb-0">Pengecekan Stok</h5></div>
                <div class="card-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Produk</th><th class="text-end">Order</th><th class="text-end">Stok Gudang</th></tr></thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-end">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((float) $item->available_stock, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cek Stok dari Gudang</button>
                </div>
            </form>
        @endif

        @if(!$invoice && auth()->user()?->hasPermission('sales_finance.create'))
            <form method="POST" action="{{ route('sales-finance.invoice.generate', $order) }}" class="card">
                @csrf
                <div class="card-header">
                    <h5 class="mb-0">Generate Invoice</h5>
                    <small class="text-muted">Invoice akan menjadi bagian dari pengumpulan periode 1 bulan sebelum digabung melalui proses SJS.</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Faktur</label>
                        <select name="tax_type" class="form-select" required>
                            <option value="js">JS</option>
                            <option value="sjb_non_pajak">SJB Non Pajak</option>
                            <option value="sjb_pajak">SJB Pajak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success w-100" @disabled($order->stock_status !== 'available')>Generate Invoice Sales</button>
                    @if($order->stock_status !== 'available')
                        <small class="text-muted d-block mt-2">Invoice aktif setelah stok available.</small>
                    @endif
                </div>
            </form>
        @endif

        @if($invoice)
            <form method="POST" action="{{ route('invoices.delivery-note.store', $invoice) }}" class="card">
                @csrf
                <div class="card-header"><h5 class="mb-0">Surat Jalan</h5></div>
                <div class="card-body">
                    @if($deliveryNote)
                        <div class="alert alert-light">Nomor: {{ $deliveryNote->delivery_note_number }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Tanggal Kirim</label>
                        <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', optional($deliveryNote?->delivery_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach(['draft', 'process', 'delivered', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(($deliveryNote?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">PIC Sales</label><input type="text" name="pic_sales" class="form-control" value="{{ $deliveryNote?->pic_sales }}"></div>
                    <div class="mb-3"><label class="form-label">PIC Gudang</label><input type="text" name="pic_gudang" class="form-control" value="{{ $deliveryNote?->pic_gudang }}"></div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Surat Jalan</button>
                </div>
            </form>

            @if($warehouseTask?->status !== 'completed')
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pembayaran</h5></div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            Pembayaran aktif setelah Warehouse Task completed.
                        </div>
                    </div>
                </div>
            @elseif((float) $invoice->outstanding_amount > 0)
                <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="card">
                    @csrf
                    <div class="card-header"><h5 class="mb-0">Pelunasan Invoice</h5></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Tanggal Bayar</label><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                        <div class="mb-3">
                            <label class="form-label">Metode</label>
                            <select name="method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="transfer_bank">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                                <option value="giro">Giro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rekening Penerimaan</label>
                            <select name="receiving_account" class="form-select">
                                <option value="js">JS</option>
                                <option value="sjb">SJB</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Nominal</label><input type="number" step="0.01" min="1" max="{{ $invoice->outstanding_amount }}" name="amount" class="form-control" value="{{ $invoice->outstanding_amount }}" required></div>
                        <div class="mb-3"><label class="form-label">Referensi</label><input type="text" name="reference_number" class="form-control"></div>
                        <button type="submit" class="btn btn-success w-100">Simpan Pembayaran</button>
                    </div>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
