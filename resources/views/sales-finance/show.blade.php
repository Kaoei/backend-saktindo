@extends('layouts.dashboard', [
    'title' => 'Detail Sales',
    'pageTitle' => 'Detail Sales',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">'.$order->id.'</li>',
])

@section('content')
@php
    $invoice = $order->invoice_record;
    $deliveryNotes = $invoice?->deliveryNotes ?? collect();
    $invoiceItems = $invoice ? $invoice->salesOrders->flatMap->items : $order->items;
    $warehouseTask = $invoice?->warehouseTask;
    $warehouseTaskReference = $warehouseTask?->id ?? $order->warehouse_task_reference ?? '-';
@endphp

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
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
                <div class="d-flex gap-2">
                    @if(auth()->user()?->hasPermission('sales_finance.edit'))
                        <a href="{{ route('sales-finance.edit', $order) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    @endif
                    @if(auth()->user()?->hasPermission('sales_finance.delete') && $order->order_status !== 'cancelled')
                        <form method="POST" action="{{ route('sales-finance.destroy', $order) }}" onsubmit="return confirm('Cancel Sales Order ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Cancel SO</button>
                        </form>
                    @endif
                </div>
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
                                <th class="text-end">Terkirim</th>
                                <th class="text-end">Sisa</th>
                                <th class="text-end">Stok</th>
                                <th>Status</th>
                                <th class="text-end">Harga Unit</th>
                                <th class="text-center">Diskon (D1+D2+D3+D4)</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @php
                                    $discParts = array_filter([
                                        $item->discount_1 ? floatval($item->discount_1).'%' : null,
                                        $item->discount_2 ? floatval($item->discount_2).'%' : null,
                                        $item->discount_3 ? floatval($item->discount_3).'%' : null,
                                        $item->discount_4 ? floatval($item->discount_4).'%' : null,
                                    ]);
                                    $discText = !empty($discParts) ? implode(' + ', $discParts) : '-';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                        <div class="small text-muted">
                                            SKU: <code>{{ $item->product_code ?: '-' }}</code>
                                            @php
                                                $gpLocs = \App\Models\GudangProduct::with('rack')->where('supplier_product_id', $item->product_code)->where('qty', '>', 0)->get();
                                            @endphp
                                            @if($gpLocs->count() > 0)
                                                <span class="badge bg-light-info text-info ms-1">
                                                    <i class="feather icon-map-pin me-1"></i>{{ $gpLocs->map(fn($gp) => strtoupper($gp->gudang_type ?: 'JS').' : '.($gp->rack->rak_kode ?? $gp->rack_id ?? '-'))->implode(', ') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $item->quantity, 2, ',', '.') }} {{ $item->unit }}</td>
                                    <td class="text-end">{{ number_format((float) $item->delivered_qty, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format(max(0, (float) $item->quantity - (float) $item->delivered_qty), 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->available_stock, 2, ',', '.') }}</td>
                                    <td><span class="badge {{ $item->stock_status === 'pending' ? 'bg-light-warning' : ($item->stock_status === 'available' ? 'bg-light-success' : 'bg-light-secondary') }}">{{ ucfirst($item->stock_status) }}</span></td>
                                    <td class="text-end">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-center"><span class="badge bg-light-primary text-primary">{{ $discText }}</span></td>
                                    <td class="text-end">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="8" class="text-end">Subtotal</th><th class="text-end">Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</th></tr>
                            <tr><th colspan="8" class="text-end">Pajak</th><th class="text-end">Rp {{ number_format((float) $order->tax_amount, 0, ',', '.') }}</th></tr>
                            <tr><th colspan="8" class="text-end">Grand Total</th><th class="text-end">Rp {{ number_format((float) $order->grand_total, 0, ',', '.') }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($order->is_pre_order || $order->order_status === 'pending_stock')
            <div class="card border-primary mb-3">
                <div class="card-header bg-light-primary d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="feather icon-clock text-primary fs-4 me-2"></i>
                        <div>
                            <h6 class="mb-0 fw-bold text-primary">Informasi Pre-Order (Indent)</h6>
                            <small class="text-muted">Pesanan ini dalam status indent / pre-order pengadaan barang.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if($order->proformaInvoice)
                            <a href="{{ route('sales-finance.proforma.print', $order) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="feather icon-printer me-1"></i> Cetak Proforma (PI)
                            </a>
                        @else
                            <form method="POST" action="{{ route('sales-finance.proforma.generate', $order) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="feather icon-file-text me-1"></i> Buat Proforma Invoice
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <div class="text-muted small">Estimasi Tiba (ETA)</div>
                            <div class="fw-bold fs-6 {{ $order->pre_order_eta ? 'text-primary' : 'text-muted' }}">
                                {{ $order->pre_order_eta ? optional($order->pre_order_eta)->format('d M Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Target DP</div>
                            <div class="fw-bold text-dark fs-6">
                                Rp {{ number_format((float) ($order->dp_amount > 0 ? $order->dp_amount : $order->grand_total), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">DP Terbayar</div>
                            <div class="fw-bold text-success fs-6">
                                Rp {{ number_format((float) $order->dp_paid, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Status DP</div>
                            <div>
                                @if($order->dp_status === 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($order->dp_status === 'partial')
                                    <span class="badge bg-info">Sebagian</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Bayar DP</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($order->pre_order_notes)
                        <div class="mt-3 p-2 bg-light rounded small text-muted">
                            <strong>Catatan Pre-Order:</strong> {{ $order->pre_order_notes }}
                        </div>
                    @endif

                    <!-- Form Catat Pembayaran DP -->
                    @if($order->dp_status !== 'paid')
                        <hr class="my-3">
                        <form method="POST" action="{{ route('sales-finance.dp-payment.store', $order) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Nominal Bayar DP (Rp)</label>
                                @php($remainingDp = max(0, ((float)($order->dp_amount > 0 ? $order->dp_amount : $order->grand_total)) - (float)$order->dp_paid))
                                <input type="number" step="0.01" min="1" name="dp_paid_amount" class="form-control form-control-sm" value="{{ $remainingDp }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Tanggal Bayar</label>
                                <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Catatan / Bukti Bayar</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Contoh: Transfer BCA">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="feather icon-check me-1"></i> Simpan DP
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        @if($invoice)
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">Invoice Management</h5>
                        <small class="text-muted">{{ $invoice->invoice_number }} - {{ ucfirst($invoice->invoice_type) }} - Faktur {{ $invoice->faktur_number }}</small>
                    </div>
                    <a href="{{ route('sales-finance.invoices.pdf', $invoice) }}" class="btn btn-outline-primary btn-sm" target="_blank">PDF Invoice</a>
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
                        <table class="table table-sm align-middle">
                            <thead><tr><th>No.</th><th>Tanggal</th><th>Metode</th><th>Detail Pembayaran / Giro</th><th>Rekening</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_number }}</td>
                                        <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                        <td><span class="badge bg-light-primary text-primary">{{ str_replace('_', ' ', strtoupper($payment->method)) }}</span></td>
                                        <td>
                                            @if($payment->method === 'giro')
                                                <div><strong>Bank:</strong> {{ $payment->bank_name ?? '-' }}</div>
                                                <div><small class="text-muted">No. Giro:</small> {{ $payment->giro_number ?? $payment->reference_number ?? '-' }}</div>
                                                <div><small class="text-muted">Jatuh Tempo:</small> {{ $payment->giro_due_date ? optional($payment->giro_due_date)->format('d M Y') : '-' }}</div>
                                                <div class="mt-1">
                                                    @if($payment->giro_status === 'cleared')
                                                        <span class="badge bg-success">Cair</span>
                                                    @elseif($payment->giro_status === 'rejected')
                                                        <span class="badge bg-danger">Ditolak</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">{{ $payment->reference_number ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ strtoupper($payment->receiving_account) }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada pembayaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mt-4">Surat Jalan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>No. Surat Jalan</th><th>Tanggal</th><th>Status</th><th>Print</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse($deliveryNotes as $note)
                                    <tr>
                                        <td>{{ $note->delivery_note_number }}</td>
                                        <td>{{ optional($note->delivery_date)->format('d M Y') }}</td>
                                        <td><span class="badge bg-light-primary">{{ ucfirst($note->status) }}</span></td>
                                        <td>{{ $note->print_count }}x</td>
                                        <td class="text-end">
                                            <a href="{{ route('sales-finance.delivery-notes.print', $note) }}" class="btn btn-sm btn-light" target="_blank">Print</a>
                                            <a href="{{ route('sales-finance.delivery-notes.pdf', $note) }}" class="btn btn-sm btn-outline-primary" target="_blank">PDF A7</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada Surat Jalan.</td></tr>
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
                <input type="hidden" name="invoice_type" value="normal">

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
                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                <div class="card-header"><h5 class="mb-0">Surat Jalan</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Kirim</label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="process">Process</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">PIC Sales</label><input type="text" name="pic_sales" class="form-control" value="{{ auth()->user()?->name }}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">PIC Gudang</label><input type="text" name="pic_gudang" class="form-control"></div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead><tr><th>Item Pesanan</th><th class="text-end">Sisa Qty Kirim</th><th class="text-end">Qty Dikirim Ini</th></tr></thead>
                            <tbody>
                                @foreach($invoiceItems as $index => $item)
                                    @php($remainingQty = max(0, (float) $item->quantity - (float) $item->delivered_qty))
                                    <tr>
                                        <td>{{ $item->product_name }}<div class="small text-muted">{{ $item->salesOrder?->id }}</div></td>
                                        <td class="text-end">{{ number_format($remainingQty, 2, ',', '.') }} {{ $item->unit }}</td>
                                        <td class="text-end" style="width: 130px;">
                                            <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $item->id }}">
                                            <input type="number" step="0.01" min="0" max="{{ $remainingQty }}" name="items[{{ $index }}][qty_sent]" class="form-control text-end" value="{{ $remainingQty }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Notes)</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Surat Jalan</button>
                </div>
            </form>

            @foreach($deliveryNotes as $note)
                <form method="POST" action="{{ route('sales-finance.delivery-notes.returns.store', $note) }}" class="card">
                    @csrf
                    <div class="card-header">
                        <h5 class="mb-0">Retur Barang</h5>
                        <small class="text-muted">{{ $note->delivery_note_number }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Tanggal Retur</label><input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Tanggal Barang Kembali</label><input type="date" name="received_date" class="form-control"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Retur</label>
                            <select name="status" class="form-select">
                                <option value="requested">Requested</option>
                                <option value="approved">Approved</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead><tr><th>Produk</th><th class="text-end">Terkirim</th><th class="text-end">Qty Retur</th></tr></thead>
                                <tbody>
                                    @foreach($note->items as $index => $noteItem)
                                        <tr>
                                            <td>{{ $noteItem->salesOrderItem?->product_name }}</td>
                                            <td class="text-end">{{ number_format((float) $noteItem->qty_sent, 2, ',', '.') }}</td>
                                            <td style="width: 150px;">
                                                <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $noteItem->sales_order_item_id }}">
                                                <input type="number" step="0.01" min="0" max="{{ $noteItem->qty_sent }}" name="items[{{ $index }}][qty_returned]" class="form-control text-end" value="0">
                                                <input type="text" name="items[{{ $index }}][reason]" class="form-control mt-1" placeholder="Alasan">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3"><label class="form-label">Catatan Retur</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                        <button type="submit" class="btn btn-warning w-100">Simpan Retur</button>
                    </div>
                </form>
            @endforeach

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
                            <select name="method" id="so-payment-method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="transfer_bank">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                                <option value="giro">Giro</option>
                            </select>
                        </div>

                        <!-- Giro Detail Section for SO Show -->
                        <div id="so-giro-fields" class="card bg-light border p-3 mb-3" style="display: none;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="feather icon-credit-card text-primary me-2"></i>
                                <h6 class="mb-0 fw-bold text-primary">Detail Warkat Giro</h6>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" name="bank_name" id="so-giro-bank" class="form-control form-control-sm" placeholder="Contoh: BCA / Mandiri / BRI">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">No. Bilyet Giro <span class="text-danger">*</span></label>
                                <input type="text" name="giro_number" id="so-giro-number" class="form-control form-control-sm" placeholder="Nomor Bilyet Giro">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Tgl Jatuh Tempo Giro <span class="text-danger">*</span></label>
                                <input type="date" name="giro_due_date" id="so-giro-due-date" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Status Giro <span class="text-danger">*</span></label>
                                <select name="giro_status" id="so-giro-status" class="form-select form-select-sm">
                                    <option value="pending">Pending (Menunggu Jatuh Tempo)</option>
                                    <option value="cleared">Cleared (Langsung Cair)</option>
                                    <option value="rejected">Rejected (Ditolak)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rekening Penerimaan</label>
                            <select name="receiving_account" class="form-select">
                                <option value="js">JS</option>
                                <option value="sjb">SJB</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Nominal</label><input type="number" step="0.01" min="1" max="{{ $invoice->outstanding_amount }}" name="amount" class="form-control" value="{{ $invoice->outstanding_amount }}" required></div>
                        <div class="mb-3"><label class="form-label">Referensi</label><input type="text" name="reference_number" class="form-control" placeholder="Contoh: No. transfer / bukti setor"></div>
                        <button type="submit" class="btn btn-success w-100">Simpan Pembayaran</button>
                    </div>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#so-payment-method').on('change', function() {
        if ($(this).val() === 'giro') {
            $('#so-giro-fields').slideDown(200);
            $('#so-giro-bank, #so-giro-number, #so-giro-due-date').prop('required', true);
        } else {
            $('#so-giro-fields').slideUp(200);
            $('#so-giro-bank, #so-giro-number, #so-giro-due-date').prop('required', false);
        }
    });
});
</script>
@endpush
