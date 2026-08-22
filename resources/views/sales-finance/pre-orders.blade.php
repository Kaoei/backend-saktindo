@extends('layouts.dashboard', [
    'title' => 'Daftar Pre-Order (Indent)',
    'pageTitle' => 'Manajemen Pre-Order (Indent)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('sales-finance.index').'">Sales & Finance</a></li><li class="breadcrumb-item">Pre-Order</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0 fw-bold">Manajemen Pre-Order (Indent)</h4>
                <p class="text-muted mb-0 small">Kelola pesanan khusus/inden, estimasi kedatangan barang (ETA), dan pembayaran uang muka (DP).</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sales-finance.create') }}" class="btn btn-primary">
                    <i class="feather icon-plus me-1"></i> Buat Pesanan Baru
                </a>
                <a href="{{ route('sales-finance.index') }}" class="btn btn-outline-secondary">
                    <i class="feather icon-list me-1"></i> Semua Order
                </a>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-primary text-white mb-0 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-white-50 mb-1">Total Pre-Order</h6>
                        <h3 class="mb-0 fw-bold text-white">{{ number_format($totalPreOrders) }}</h3>
                    </div>
                    <div class="flex-shrink-0 bg-white bg-opacity-25 p-3 rounded">
                        <i class="feather icon-clock fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-warning text-dark mb-0 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-dark text-opacity-75 mb-1">Menunggu DP</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalPendingDp) }}</h3>
                    </div>
                    <div class="flex-shrink-0 bg-dark bg-opacity-10 p-3 rounded">
                        <i class="feather icon-alert-circle fs-3 text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-success text-white mb-0 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-white-50 mb-1">Total DP Diterima</h6>
                        <h4 class="mb-0 fw-bold text-white">Rp {{ number_format($totalDpCollected, 0, ',', '.') }}</h4>
                    </div>
                    <div class="flex-shrink-0 bg-white bg-opacity-25 p-3 rounded">
                        <i class="feather icon-dollar-sign fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-info text-white mb-0 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-white-50 mb-1">Estimasi Tiba (7 Hari)</h6>
                        <h3 class="mb-0 fw-bold text-white">{{ number_format($upcomingEtaCount) }}</h3>
                    </div>
                    <div class="flex-shrink-0 bg-white bg-opacity-25 p-3 rounded">
                        <i class="feather icon-truck fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Search -->
<div class="card mb-4 border shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('sales-finance.pre-orders.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="feather icon-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari No. SO, No. PO, atau Customer..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="dp_status" class="form-select">
                    <option value="">-- Semua Status DP --</option>
                    <option value="unpaid" @selected(request('dp_status') === 'unpaid')>Belum Bayar DP</option>
                    <option value="partial" @selected(request('dp_status') === 'partial')>DP Sebagian</option>
                    <option value="paid" @selected(request('dp_status') === 'paid')>DP Lunas</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="customer_id" class="form-select">
                    <option value="">-- Semua Customer --</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}" @selected(request('customer_id') == $cust->id)>{{ $cust->nama_customer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                @if(request()->anyFilled(['search', 'dp_status', 'customer_id']))
                    <a href="{{ route('sales-finance.pre-orders.index') }}" class="btn btn-light"><i class="feather icon-refresh-cw"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. SO & PO Cust</th>
                        <th>Customer</th>
                        <th>Item Pesanan</th>
                        <th>Estimasi Kedatangan (ETA)</th>
                        <th>Grand Total & DP</th>
                        <th>Status DP</th>
                        <th>Status Stok</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preOrders as $so)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('sales-finance.show', $so) }}" class="fw-bold text-primary">{{ $so->id }}</a>
                                <div><small class="text-muted">PO: {{ $so->customer_po_number ?: '-' }}</small></div>
                                <small class="text-muted">{{ optional($so->order_date)->format('d M Y') }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $so->customer_name }}</div>
                                @if($so->customer?->nomor_hp)
                                    <small class="text-muted"><i class="feather icon-phone me-1"></i>{{ $so->customer->nomor_hp }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light-secondary text-dark">{{ $so->items->count() }} Produk</span>
                                <div class="small text-muted text-truncate" style="max-width: 180px;">
                                    {{ $so->items->pluck('product_name')->implode(', ') }}
                                </div>
                            </td>
                            <td>
                                @if($so->pre_order_eta)
                                    @php
                                        $eta = \Carbon\Carbon::parse($so->pre_order_eta);
                                        $isOverdue = $eta->isPast() && !$eta->isToday();
                                        $isSoon = $eta->isBetween(now(), now()->addDays(5));
                                    @endphp
                                    <div class="fw-bold {{ $isOverdue ? 'text-danger' : ($isSoon ? 'text-warning' : 'text-dark') }}">
                                        <i class="feather icon-calendar me-1"></i> {{ $eta->format('d M Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <span class="badge bg-danger">Lewat Estimasi</span>
                                    @elseif($isSoon)
                                        <span class="badge bg-warning text-dark">Tiba Sebentar Lagi</span>
                                    @else
                                        <small class="text-muted">{{ $eta->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="text-muted italic">- Belum diset -</span>
                                @endif
                            </td>
                            <td>
                                <div><strong class="text-dark">Rp {{ number_format((float) $so->grand_total, 0, ',', '.') }}</strong></div>
                                <small class="text-muted">
                                    Target DP: Rp {{ number_format((float) $so->dp_amount, 0, ',', '.') }}
                                </small>
                                @if((float) $so->dp_paid > 0)
                                    <div class="small text-success fw-semibold">
                                        Dibayar: Rp {{ number_format((float) $so->dp_paid, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($so->dp_status === 'paid')
                                    <span class="badge bg-success"><i class="feather icon-check-circle me-1"></i> DP Lunas</span>
                                @elseif($so->dp_status === 'partial')
                                    <span class="badge bg-info"><i class="feather icon-pie-chart me-1"></i> DP Sebagian</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="feather icon-clock me-1"></i> Belum DP</span>
                                @endif
                            </td>
                            <td>
                                @if($so->stock_status === 'available')
                                    <span class="badge bg-success">Stok Ready</span>
                                @else
                                    <span class="badge bg-light-warning text-dark">Indent / Pending</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Pilihan
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item" href="{{ route('sales-finance.show', $so) }}"><i class="feather icon-eye me-2"></i> Detail Order</a></li>
                                        
                                        @if($so->proformaInvoice)
                                            <li><a class="dropdown-item" href="{{ route('sales-finance.proforma.print', $so) }}" target="_blank"><i class="feather icon-printer me-2"></i> Cetak Proforma (PI)</a></li>
                                        @else
                                            <li>
                                                <form method="POST" action="{{ route('sales-finance.proforma.generate', $so) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="feather icon-file-text me-2"></i> Buat Proforma (PI)</button>
                                                </form>
                                            </li>
                                        @endif

                                        <li>
                                            <button type="button" class="dropdown-item btn-record-dp" 
                                                    data-id="{{ $so->id }}"
                                                    data-customer="{{ $so->customer_name }}"
                                                    data-target-dp="{{ $so->dp_amount > 0 ? $so->dp_amount : $so->grand_total }}"
                                                    data-dp-paid="{{ $so->dp_paid }}"
                                                    data-action="{{ route('sales-finance.dp-payment.store', $so) }}">
                                                <i class="feather icon-dollar-sign me-2"></i> Catat Pembayaran DP
                                            </button>
                                        </li>

                                        @if($so->stock_status !== 'available')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-primary" href="{{ route('supplier-po.create-from-shortage', ['shortage_items' => $so->items->map(fn($it) => ['product_code' => $it->product_code, 'product_name' => $it->product_name, 'qty' => $it->quantity, 'unit' => $it->unit, 'price' => $it->unit_price])->toArray()]) }}">
                                                    <i class="feather icon-shopping-cart me-2"></i> Pesan ke Supplier (PO)
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="feather icon-package fs-1 d-block mb-2 text-muted"></i>
                                Tidak ada data Pre-Order (Indent) yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($preOrders->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $preOrders->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Catat DP -->
<div class="modal fade" id="modalRecordDp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formRecordDp" action="" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Catat Pembayaran Uang Muka (DP)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-0 small">Customer & Pesanan</label>
                    <div class="fw-bold text-dark fs-6" id="dpModalCustomer">-</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted mb-0 small">Target DP</label>
                        <div class="fw-semibold text-primary" id="dpModalTarget">Rp 0</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted mb-0 small">Sudah Dibayar</label>
                        <div class="fw-semibold text-success" id="dpModalPaid">Rp 0</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal DP Diterima (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="1" name="dp_paid_amount" id="dpModalAmountInput" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Penerimaan DP <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan / Bukti Bayar</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Contoh: Transfer BCA an Budi / Bukti transfer terlampir"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan Pembayaran DP</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.btn-record-dp').on('click', function() {
        const action = $(this).data('action');
        const customer = $(this).data('customer');
        const id = $(this).data('id');
        const targetDp = parseFloat($(this).data('target-dp')) || 0;
        const dpPaid = parseFloat($(this).data('dp-paid')) || 0;
        const remaining = Math.max(0, targetDp - dpPaid);

        $('#formRecordDp').attr('action', action);
        $('#dpModalCustomer').text(id + ' - ' + customer);
        $('#dpModalTarget').text('Rp ' + new Intl.NumberFormat('id-ID').format(targetDp));
        $('#dpModalPaid').text('Rp ' + new Intl.NumberFormat('id-ID').format(dpPaid));
        $('#dpModalAmountInput').val(remaining > 0 ? remaining : targetDp);

        new bootstrap.Modal(document.getElementById('modalRecordDp')).show();
    });
});
</script>
@endpush
