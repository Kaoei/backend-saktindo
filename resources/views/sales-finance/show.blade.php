@extends('layouts.dashboard', [
    'title' => 'Detail Sales',
    'pageTitle' => 'Detail Sales',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">'.$order->id.'</li>',
])

@section('content')
@php
    $invoice = $order->invoice;

    $deliveryNotes = $invoice?->deliveryNotes ?? collect();

    $invoiceItems = $invoice
        ? $invoice->salesOrder?->items ?? collect()
        : $order->items;

    $warehouseTask = $invoice?->warehouseTask;

    $warehouseTaskReference =
        $warehouseTask?->id
        ?? $order->warehouse_task_reference
        ?? '-';
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
                                @php
                                    $remainingDp = max(0, ((float)($order->dp_amount > 0 ? $order->dp_amount : $order->grand_total)) - (float)$order->dp_paid);
                                @endphp
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
                        <small class="text-muted">{{ $invoice->invoice_number }} - {{ ucfirst($invoice->invoice_type) }} - Faktur {{ $invoice->faktur_number }}
                            @if($invoice->faktur_checked)
                                <span class="badge bg-success ms-1">✓ Dicek</span>
                            @endif
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($invoice->status !== 'paid' && $invoice->outstanding_amount > 0)
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#sendInvoiceEmailModal" data-toggle="modal" data-target="#sendInvoiceEmailModal">
                                <i class="feather icon-mail me-1"></i> Kirim Tagihan Email
                            </button>
                        @endif
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editFakturModal" data-toggle="modal" data-target="#editFakturModal">
                            <i class="feather icon-printer me-1"></i> Cetak / Sesuaikan Faktur
                        </button>
                        <a href="{{ route('sales-finance.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="Download Faktur Default">
                            <i class="feather icon-download me-1"></i> PDF Cepat
                        </a>
                    </div>

                {{-- Faktur Checklist Bar --}}
                @if($invoice->faktur_number)
                <div class="px-3 py-2 border-bottom {{ $invoice->faktur_checked ? 'bg-success-subtle' : 'bg-warning-subtle' }}">
                    @if($invoice->faktur_checked)
                        <div class="d-flex align-items-center gap-2 text-success">
                            <i class="feather icon-check-circle"></i>
                            <small class="fw-semibold">Faktur sudah dicek pada {{ optional($invoice->faktur_checked_at)->format('d M Y H:i') }}</small>
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-warning-emphasis fw-semibold"><i class="feather icon-alert-circle me-1"></i>Faktur belum dicek</small>
                            <form method="POST" action="{{ route('invoices.check-faktur', $invoice) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Tandai faktur ini sudah dicek? Tindakan ini tidak dapat dibatalkan.')">
                                    <i class="feather icon-check me-1"></i>Tandai Sudah Dicek
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                @endif
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
                                    @php
                                        $remainingQty = max(0, (float) $item->quantity - (float) $item->delivered_qty);
                                    @endphp
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
            @if($isHistoricalInvoice && $activeMergeInvoice)

                <div class="card border-info">
                    <div class="card-header">
                        <h5 class="mb-0">Pembayaran</h5>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-info mb-3">
                            Invoice
                            <strong>{{ $invoice->invoice_number }}</strong>
                            sudah digabung/diteruskan ke invoice combination
                            <strong>{{ $activeMergeInvoice->invoice_number }}</strong>.

                            <div class="mt-1">
                                Pembayaran hanya dapat dilakukan pada invoice combination tersebut.
                            </div>
                        </div>

                        <a
                            href="{{ route('sales-finance.show', $activeMergeInvoice->salesOrder) }}"
                            class="btn btn-info w-100"
                        >
                            <i class="feather icon-arrow-right-circle me-1"></i>
                            Buka Invoice Combination
                            {{ $activeMergeInvoice->invoice_number }}
                        </a>

                    </div>
                </div>

            @elseif(!$warehouseReady)

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pembayaran</h5>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-warning mb-0">

                            @if($invoice->invoice_type === 'gabungan')

                                Pembayaran combination aktif setelah semua Warehouse Task
                                dari Sales Order sumber berstatus completed.

                            @else

                                Pembayaran aktif setelah Warehouse Task completed.

                            @endif

                        </div>

                        @if($invoice->invoice_type === 'gabungan' && $sourceSalesOrders->isNotEmpty())

                            <ul class="small text-muted mt-2 mb-0 ps-3">

                                @foreach($sourceSalesOrders as $sourceSalesOrder)

                                    @php
                                        $sourceTask = $sourceWarehouseTasks->get($sourceSalesOrder->id);
                                    @endphp

                                    <li>
                                        {{ $sourceSalesOrder->id }} —

                                        @if($sourceTask)

                                            {{ $sourceTask->id }}

                                            (
                                            <span class="{{ $sourceTask->status === 'completed' ? 'text-success' : 'text-warning' }}">
                                                {{ ucfirst($sourceTask->status) }}
                                            </span>
                                            )

                                        @else

                                            Belum ada Warehouse Task

                                        @endif

                                    </li>

                                @endforeach

                            </ul>

                        @endif

                    </div>
                </div>

            @elseif((float) $invoice->outstanding_amount > 0)

                <form
                    method="POST"
                    action="{{ route('invoices.payments.store', $invoice) }}"
                    class="card"
                >

                    @csrf

                    <div class="card-header">
                        <h5 class="mb-0">Pelunasan Invoice</h5>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar</label>

                            <input
                                type="date"
                                name="payment_date"
                                class="form-control"
                                value="{{ now()->toDateString() }}"
                                required
                            >
                        </div>

                        <div class="mb-3">

                            <label class="form-label">Metode</label>

                            <select
                                name="method"
                                id="so-payment-method"
                                class="form-select"
                            >
                                <option value="cash">Cash</option>
                                <option value="transfer_bank">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                                <option value="giro">Giro</option>
                            </select>

                        </div>

                        <div
                            id="so-giro-fields"
                            class="card bg-light border p-3 mb-3"
                            style="display:none;"
                        >

                            <div class="d-flex align-items-center mb-2">
                                <i class="feather icon-credit-card text-primary me-2"></i>
                                <h6 class="mb-0 fw-bold text-primary">
                                    Detail Warkat Giro
                                </h6>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">
                                    Nama Bank <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="bank_name"
                                    id="so-giro-bank"
                                    class="form-control form-control-sm"
                                    placeholder="Contoh: BCA / Mandiri / BRI"
                                >
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">
                                    No. Bilyet Giro <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="giro_number"
                                    id="so-giro-number"
                                    class="form-control form-control-sm"
                                    placeholder="Nomor Bilyet Giro"
                                >
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">
                                    Tgl Jatuh Tempo Giro <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="giro_due_date"
                                    id="so-giro-due-date"
                                    class="form-control form-control-sm"
                                    value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                                >
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">
                                    Status Giro <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="giro_status"
                                    id="so-giro-status"
                                    class="form-select form-select-sm"
                                >
                                    <option value="pending">
                                        Pending (Menunggu Jatuh Tempo)
                                    </option>

                                    <option value="cleared">
                                        Cleared (Langsung Cair)
                                    </option>

                                    <option value="rejected">
                                        Rejected (Ditolak)
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Rekening Penerimaan
                            </label>

                            <select
                                name="receiving_account"
                                class="form-select"
                            >
                                <option value="js">JS</option>
                                <option value="sjb">SJB</option>
                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">Nominal</label>

                            <input
                                type="number"
                                step="0.01"
                                min="1"
                                max="{{ $invoice->outstanding_amount }}"
                                name="amount"
                                class="form-control"
                                value="{{ $invoice->outstanding_amount }}"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">Referensi</label>

                            <input
                                type="text"
                                name="reference_number"
                                class="form-control"
                                placeholder="Contoh: No. transfer / bukti setor"
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >
                            Simpan Pembayaran
                        </button>

                    </div>

                </form>

            @endif
            @endif
                </div>
            </div>


@if($invoice)
<!-- Modal Kirim Email Tagihan -->
<div class="modal fade" id="sendInvoiceEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('finance.invoices.send-email', $invoice->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold"><i class="feather icon-mail me-1"></i> Kirim Tagihan by Email</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Invoice</label>
                        <input type="text" class="form-control" value="{{ $invoice->invoice_number }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer</label>
                        <input type="text" class="form-control" value="{{ $order->customer_name ?? '-' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Tujuan <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $order->customer?->email ?? '' }}" placeholder="contoh: finance@perusahaan.com" required>
                        <small class="text-muted">Rincian invoice dan rekening pembayaran akan dikirim ke email ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pesan Tambahan (Opsional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Pesan khusus untuk client"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="feather icon-send me-1"></i> Kirim Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cetak / Sesuaikan Faktur Penjualan -->
@php
    $modalItems = $invoiceItems;
    if ($modalItems->isEmpty() && isset($invoice->salesOrders) && $invoice->salesOrders->isNotEmpty()) {
        $modalItems = $invoice->salesOrders->flatMap(fn($so) => $so->items ?? collect());
    }
    $modalCustomerName = $order->customer_name ?? $order->customer?->nama_customer ?? ($invoice->salesOrders?->first()?->customer_name ?? '');
    $modalCustomerAddress = $order->customer?->alamat 
        ? $order->customer->alamat . (!empty($order->customer->kota) ? ', ' . $order->customer->kota : '')
        : ($order->shipping_address ?? '');
    $modalPoNumber = $order->customer_po_number ?? '';
    if (empty($modalPoNumber) && isset($invoice->salesOrders) && $invoice->salesOrders->isNotEmpty()) {
        $modalPoNumber = $invoice->salesOrders->pluck('customer_po_number')->filter()->unique()->join(', ');
    }
@endphp
<div class="modal fade" id="editFakturModal" tabindex="-1" aria-labelledby="editFakturModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formEditFaktur" method="POST" action="{{ route('sales-finance.invoices.pdf', $invoice) }}" target="_blank">
                @csrf
                <input type="hidden" name="action" id="faktur-action" value="stream">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white fw-bold" id="editFakturModalLabel">
                        <i class="feather icon-printer me-2"></i> Sesuaikan & Cetak Faktur Penjualan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="feather icon-info me-2 fs-5"></i>
                        <div>
                            Anda dapat mengubah keterangan, nama barang, harga, diskon, dan total sebelum mencetak faktur agar tidak ada data yang kosong sesuai format faktur fisik PT. SAKTINDO JAYA BERSAMA.
                        </div>
                    </div>

                    <!-- Dokumen & Pelanggan Info -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-white py-2 fw-bold text-primary">
                                    <i class="feather icon-file-text me-1"></i> Informasi Faktur
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">No. Faktur</label>
                                            <input type="text" name="invoice_number" class="form-control form-control-sm" value="{{ $invoice->invoice_number }}">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">No. Reff (PO Customer)</label>
                                            <input type="text" name="po_number" class="form-control form-control-sm" value="{{ $modalPoNumber }}">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">Tanggal Faktur</label>
                                            <input type="date" name="invoice_date" class="form-control form-control-sm" value="{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : date('Y-m-d') }}">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">Jatuh Tempo</label>
                                            <input type="date" name="due_date" class="form-control form-control-sm" value="{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">Keterangan (misal: Tk. 238)</label>
                                            <input type="text" name="notes" class="form-control form-control-sm" value="{{ $order->notes ?? '' }}" placeholder="Contoh: Tk. 238">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">No. Seri Faktur Pajak</label>
                                            <input type="text" name="faktur_pajak" class="form-control form-control-sm" value="{{ $invoice->faktur_number ?? '' }}" placeholder="-">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-white py-2 fw-bold text-primary">
                                    <i class="feather icon-user me-1"></i> Kepada Yth (Pelanggan)
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Nama Customer</label>
                                        <input type="text" name="customer_name" class="form-control form-control-sm" value="{{ $modalCustomerName }}" required>
                                    </div>
                                    <div>
                                        <label class="form-label small fw-semibold">Alamat Customer</label>
                                        <textarea name="customer_address" class="form-control form-control-sm" rows="3" placeholder="Alamat lengkap...">{{ $modalCustomerAddress }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Item Barang -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-primary"><i class="feather icon-box me-1"></i> Rincian Barang</span>
                            <button type="button" class="btn btn-sm btn-outline-success" id="btnAddFakturRow">
                                <i class="feather icon-plus me-1"></i> Tambah Baris Barang
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="fakturItemsTable">
                                    <thead class="table-light text-center small">
                                        <tr>
                                            <th style="width: 40px;">No</th>
                                            <th>Nama Barang</th>
                                            <th style="width: 90px;">Qty</th>
                                            <th style="width: 90px;">Satuan</th>
                                            <th style="width: 140px;">Harga (Rp)</th>
                                            <th style="width: 75px;">Disc 1%</th>
                                            <th style="width: 75px;">Disc 2%</th>
                                            <th style="width: 150px;">Jumlah (Rp)</th>
                                            <th style="width: 45px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="fakturItemsBody">
                                        @forelse($modalItems as $idx => $it)
                                        <tr class="faktur-item-row">
                                            <td class="text-center row-num">{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][item_name]" class="form-control form-control-sm item-name" value="{{ $it->item_name }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" name="items[{{ $idx }}][qty]" class="form-control form-control-sm text-end item-qty" value="{{ $it->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][unit]" class="form-control form-control-sm text-center item-unit" value="{{ $it->unit ?? 'ROL' }}">
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm text-end item-price" value="{{ $it->unit_price }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" max="100" name="items[{{ $idx }}][discount1]" class="form-control form-control-sm text-end item-d1" value="{{ $it->discount1 ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" max="100" name="items[{{ $idx }}][discount2]" class="form-control form-control-sm text-end item-d2" value="{{ $it->discount2 ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" step="any" name="items[{{ $idx }}][subtotal]" class="form-control form-control-sm text-end item-subtotal" value="{{ $it->subtotal ?? ($it->quantity * $it->unit_price) }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-row" title="Hapus"><i class="feather icon-trash-2"></i></button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr class="faktur-item-row">
                                            <td class="text-center row-num">1</td>
                                            <td>
                                                <input type="text" name="items[0][item_name]" class="form-control form-control-sm item-name" value="FLEX. STEEL 1/2&quot; @ 50M" required>
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" name="items[0][qty]" class="form-control form-control-sm text-end item-qty" value="1" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][unit]" class="form-control form-control-sm text-center item-unit" value="ROL">
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" name="items[0][unit_price]" class="form-control form-control-sm text-end item-price" value="370000" required>
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" max="100" name="items[0][discount1]" class="form-control form-control-sm text-end item-d1" value="0">
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" max="100" name="items[0][discount2]" class="form-control form-control-sm text-end item-d2" value="0">
                                            </td>
                                            <td>
                                                <input type="number" step="any" name="items[0][subtotal]" class="form-control form-control-sm text-end item-subtotal" value="370000">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-row" title="Hapus"><i class="feather icon-trash-2"></i></button>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Perhitungan & Terbilang -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-white py-2 fw-bold text-primary">
                                    <i class="feather icon-info me-1"></i> Klausul Perhatian (Notes Cetak)
                                </div>
                                <div class="card-body p-3 small text-secondary">
                                    <ol class="mb-0 ps-3">
                                        <li class="mb-1">Barang-barang yang telah dibeli tidak dapat dikembalikan.</li>
                                        <li class="mb-1">Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan.</li>
                                        <li>CEK/GIRO atas nama : <strong>PT. SAKTINDO JAYA BERSAMA</strong>. BCA KENARI . REK NO. 068.3055678</li>
                                    </ol>
                                    <hr class="my-2">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-dark">Terbilang</label>
                                        <input type="text" name="terbilang" id="fakturTerbilangInput" class="form-control form-control-sm" value="" placeholder="Otomatis terisi...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-white py-2 fw-bold text-primary">
                                    <i class="feather icon-dollar-sign me-1"></i> Ringkasan Nilai
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2 row align-items-center">
                                        <label class="col-sm-5 col-form-label col-form-label-sm small fw-semibold">Subtotal (Rp)</label>
                                        <div class="col-sm-7">
                                            <input type="number" step="any" name="subtotal" id="fakturSubtotalInput" class="form-control form-control-sm text-end" value="{{ $invoice->subtotal ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="mb-2 row align-items-center">
                                        <label class="col-sm-5 col-form-label col-form-label-sm small fw-semibold">PPN / Tax (Rp)</label>
                                        <div class="col-sm-7">
                                            <input type="number" step="any" name="tax_amount" id="fakturTaxInput" class="form-control form-control-sm text-end" value="{{ $invoice->tax_amount ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <label class="col-sm-5 col-form-label col-form-label-sm small fw-bold text-primary">Total Rp</label>
                                        <div class="col-sm-7">
                                            <input type="number" step="any" name="grand_total" id="fakturGrandTotalInput" class="form-control form-control-sm text-end fw-bold text-primary" value="{{ $invoice->grand_total ?? 0 }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penandatangan & Footer Cetak -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-2 fw-bold text-primary">
                            <i class="feather icon-check-square me-1"></i> Info Tanda Tangan & Komputer
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold">Nama Penandatangan</label>
                                    <input type="text" name="signer_name" class="form-control form-control-sm" value="FENIKI">
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold">Jabatan</label>
                                    <input type="text" name="signer_title" class="form-control form-control-sm" value="DIREKTUR">
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold">User Operator</label>
                                    <input type="text" name="user_name" class="form-control form-control-sm" value="{{ auth()->user()?->name ?? 'SALSA' }}">
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold">Nama Komputer</label>
                                    <input type="text" name="computer_name" class="form-control form-control-sm" value="JAYA">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-outline-primary" onclick="$('#faktur-action').val('stream');">
                        <i class="feather icon-eye me-1"></i> Preview & Cetak Langsung
                    </button>
                    <button type="submit" class="btn btn-primary" onclick="$('#faktur-action').val('download');">
                        <i class="feather icon-download me-1"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
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

    // Helper terbilang JS
    function angkaTerbilang(angka) {
        angka = Math.floor(Math.abs(Number(angka))) || 0;
        var huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if (angka < 12) return huruf[angka];
        if (angka < 20) return (angkaTerbilang(angka - 10) + ' Belas').trim();
        if (angka < 100) return (angkaTerbilang(Math.floor(angka / 10)) + ' Puluh ' + huruf[angka % 10]).trim();
        if (angka < 200) return ('Seratus ' + angkaTerbilang(angka - 100)).trim();
        if (angka < 1000) return (angkaTerbilang(Math.floor(angka / 100)) + ' Ratus ' + angkaTerbilang(angka % 100)).trim();
        if (angka < 2000) return ('Seribu ' + angkaTerbilang(angka - 1000)).trim();
        if (angka < 1000000) return (angkaTerbilang(Math.floor(angka / 1000)) + ' Ribu ' + angkaTerbilang(angka % 1000)).trim();
        if (angka < 1000000000) return (angkaTerbilang(Math.floor(angka / 1000000)) + ' Juta ' + angkaTerbilang(angka % 1000000)).trim();
        if (angka < 1000000000000) return (angkaTerbilang(Math.floor(angka / 1000000000)) + ' Milyar ' + angkaTerbilang(angka % 1000000000)).trim();
        return '';
    }

    function recalculateFakturTotals() {
        var subtotal = 0;
        $('#fakturItemsBody .faktur-item-row').each(function(i, row) {
            $(row).find('.row-num').text(i + 1);
            var qty = parseFloat($(row).find('.item-qty').val()) || 0;
            var price = parseFloat($(row).find('.item-price').val()) || 0;
            var d1 = parseFloat($(row).find('.item-d1').val()) || 0;
            var d2 = parseFloat($(row).find('.item-d2').val()) || 0;
            
            var rowTotal = qty * price;
            if (d1 > 0) rowTotal = rowTotal * (1 - (d1 / 100));
            if (d2 > 0) rowTotal = rowTotal * (1 - (d2 / 100));
            rowTotal = Math.round(rowTotal * 100) / 100;
            
            $(row).find('.item-subtotal').val(rowTotal);
            subtotal += rowTotal;
        });

        $('#fakturSubtotalInput').val(subtotal);
        var tax = parseFloat($('#fakturTaxInput').val()) || 0;
        var grandTotal = subtotal + tax;
        $('#fakturGrandTotalInput').val(grandTotal);

        var terbilang = angkaTerbilang(grandTotal);
        if (terbilang) {
            $('#fakturTerbilangInput').val(terbilang + ' Rupiah');
        } else {
            $('#fakturTerbilangInput').val('Nol Rupiah');
        }
    }

    $(document).on('input', '.item-qty, .item-price, .item-d1, .item-d2', function() {
        recalculateFakturTotals();
    });

    $('#fakturTaxInput').on('input', function() {
        var subtotal = parseFloat($('#fakturSubtotalInput').val()) || 0;
        var tax = parseFloat($(this).val()) || 0;
        var grandTotal = subtotal + tax;
        $('#fakturGrandTotalInput').val(grandTotal);
        var terbilang = angkaTerbilang(grandTotal);
        $('#fakturTerbilangInput').val(terbilang ? terbilang + ' Rupiah' : 'Nol Rupiah');
    });

    $('#fakturSubtotalInput').on('input', function() {
        var subtotal = parseFloat($(this).val()) || 0;
        var tax = parseFloat($('#fakturTaxInput').val()) || 0;
        var grandTotal = subtotal + tax;
        $('#fakturGrandTotalInput').val(grandTotal);
        var terbilang = angkaTerbilang(grandTotal);
        $('#fakturTerbilangInput').val(terbilang ? terbilang + ' Rupiah' : 'Nol Rupiah');
    });

    $('#fakturGrandTotalInput').on('input', function() {
        var grandTotal = parseFloat($(this).val()) || 0;
        var terbilang = angkaTerbilang(grandTotal);
        $('#fakturTerbilangInput').val(terbilang ? terbilang + ' Rupiah' : 'Nol Rupiah');
    });

    // Tambah baris barang
    $('#btnAddFakturRow').on('click', function() {
        var index = $('#fakturItemsBody .faktur-item-row').length;
        var rowHtml = `
            <tr class="faktur-item-row">
                <td class="text-center row-num">${index + 1}</td>
                <td>
                    <input type="text" name="items[${index}][item_name]" class="form-control form-control-sm item-name" placeholder="Nama barang..." required>
                </td>
                <td>
                    <input type="number" step="any" min="0" name="items[${index}][qty]" class="form-control form-control-sm text-end item-qty" value="1" required>
                </td>
                <td>
                    <input type="text" name="items[${index}][unit]" class="form-control form-control-sm text-center item-unit" value="ROL">
                </td>
                <td>
                    <input type="number" step="any" min="0" name="items[${index}][unit_price]" class="form-control form-control-sm text-end item-price" value="0" required>
                </td>
                <td>
                    <input type="number" step="any" min="0" max="100" name="items[${index}][discount1]" class="form-control form-control-sm text-end item-d1" value="0">
                </td>
                <td>
                    <input type="number" step="any" min="0" max="100" name="items[${index}][discount2]" class="form-control form-control-sm text-end item-d2" value="0">
                </td>
                <td>
                    <input type="number" step="any" name="items[${index}][subtotal]" class="form-control form-control-sm text-end item-subtotal" value="0">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-row" title="Hapus"><i class="feather icon-trash-2"></i></button>
                </td>
            </tr>
        `;
        $('#fakturItemsBody').append(rowHtml);
        recalculateFakturTotals();
    });

    // Hapus baris barang
    $(document).on('click', '.btn-del-row', function() {
        if ($('#fakturItemsBody .faktur-item-row').length > 1) {
            $(this).closest('tr').remove();
            recalculateFakturTotals();
        } else {
            alert('Minimal satu baris barang harus ada.');
        }
    });

    // Hitung awal saat modal dibuka
    $('#editFakturModal').on('shown.bs.modal', function() {
        if (!$('#fakturTerbilangInput').val()) {
            recalculateFakturTotals();
        }
    });
});
</script>
@endpush
