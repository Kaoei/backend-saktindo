@extends('layouts.dashboard', [
    'title' => 'Detail Hutang Supplier',
    'pageTitle' => 'Detail Hutang Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.ap').'">Hutang (AP)</a></li><li class="breadcrumb-item">Detail Hutang</li>'
])

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status') || session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('status') ?? session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        {{-- Card Informasi PO & Tagihan --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-semibold">Informasi Purchase Order & Tagihan</h5>
                <small class="text-muted">Detail pemesanan barang dan kewajiban hutang supplier</small>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="35%" class="bg-light">No. PO</th>
                        <td class="fw-bold">{{ $supplierPo->po_number }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">No. Invoice / Ref. Supplier</th>
                        <td>{{ $supplierPo->reference_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Supplier</th>
                        <td>
                            <div class="fw-semibold">{{ $supplierPo->supplier->name ?? '-' }}</div>
                            @if($supplierPo->supplier?->company_name && $supplierPo->supplier->company_name !== $supplierPo->supplier->name)
                                <small class="text-muted">{{ $supplierPo->supplier->company_name }}</small>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tanggal Pemesanan</th>
                        <td>{{ $supplierPo->order_date ? $supplierPo->order_date->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Jatuh Tempo</th>
                        <td>
                            @if($supplierPo->due_date)
                                <span class="text-danger fw-semibold">{{ $supplierPo->due_date->format('d M Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status Barang Masuk</th>
                        <td>
                            @if($supplierPo->status === 'received')
                                <span class="badge bg-success">Received (Barang Sudah Diterima)</span>
                            @elseif($supplierPo->status === 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending (Menunggu Penerimaan)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status Pembayaran</th>
                        <td>
                            @php
                                $status = $supplierPo->payment_status ?: 'unpaid';
                            @endphp
                            @if($status === 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($status === 'partial')
                                <span class="badge bg-warning text-dark">Sebagian (Partial)</span>
                            @else
                                <span class="badge bg-danger">Belum Bayar</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Card Rincian Barang --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-semibold">Rincian Barang yang Dipesan</h5>
                <small class="text-muted">Daftar item produk dalam Purchase Order</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th>Nama Barang / Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-center">Diskon (%)</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplierPo->items as $index => $item)
                                @php
                                    $unitPrice = (float) $item->price;
                                    $qty = (int) $item->qty;
                                    $d1 = (float) ($item->discount_1 ?? 0);
                                    $d2 = (float) ($item->discount_2 ?? 0);
                                    $d3 = (float) ($item->discount_3 ?? 0);
                                    $d4 = (float) ($item->discount_4 ?? 0);
                                    $netUnitPrice = $unitPrice * (1 - $d1/100) * (1 - $d2/100) * (1 - $d3/100) * (1 - $d4/100);
                                    $itemSubtotal = $netUnitPrice * $qty;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $item->supplierProduct?->item_name ?? 'Item PO' }}</div>
                                        @if($item->supplierProduct?->sku)
                                            <small class="text-muted">SKU: {{ $item->supplierProduct->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($qty, 0) }}</td>
                                    <td class="text-end">Rp {{ number_format($unitPrice, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @php
                                            $discounts = array_filter([$d1, $d2, $d3, $d4], fn($d) => $d > 0);
                                        @endphp
                                        @if(!empty($discounts))
                                            <span class="badge bg-light text-dark border">{{ implode(' + ', $discounts) }}%</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($itemSubtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Tidak ada data item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end fw-semibold">Subtotal</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($supplierPo->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if((float) $supplierPo->additional_discount > 0)
                                <tr>
                                    <td colspan="5" class="text-end text-danger">Diskon Tambahan</td>
                                    <td class="text-end text-danger">- Rp {{ number_format($supplierPo->additional_discount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if((float) $supplierPo->tax_amount > 0)
                                <tr>
                                    <td colspan="5" class="text-end text-muted">PPN (11%)</td>
                                    <td class="text-end text-muted">Rp {{ number_format($supplierPo->tax_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="text-end fw-bold text-dark">Grand Total Tagihan</td>
                                <td class="text-end fw-bold text-dark">Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Card Riwayat Pembayaran --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-semibold">Riwayat Pembayaran Hutang</h5>
                <small class="text-muted">Catatan pelunasan dan bukti bayar ke supplier</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Bank / Referensi</th>
                                <th class="text-end">Jumlah</th>
                                <th>Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplierPo->payments as $payment)
                                <tr>
                                    <td class="fw-semibold">{{ $payment->payment_number }}</td>
                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-light-primary text-primary border">
                                            {{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($payment->bank_name)
                                            <div><strong>Bank:</strong> {{ $payment->bank_name }}</div>
                                        @endif
                                        @if($payment->reference_number)
                                            <div><small class="text-muted">Ref:</small> {{ $payment->reference_number }}</div>
                                        @endif
                                        @if(!$payment->bank_name && !$payment->reference_number)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $payment->user->name ?? 'System' }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pembayaran hutang untuk transaksi ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Card Ringkasan Hutang --}}
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Ringkasan Hutang</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted text-uppercase d-block">Total Tagihan</small>
                    <h4 class="fw-bold text-dark mb-0">
                        Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}
                    </h4>
                </div>

                <div class="mb-3">
                    <small class="text-muted text-uppercase d-block">Sudah Terbayar</small>
                    <h5 class="fw-bold text-success mb-0">
                        Rp {{ number_format($supplierPo->paid_amount ?? 0, 0, ',', '.') }}
                    </h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted text-uppercase d-block">Sisa Hutang (Outstanding)</small>
                    <h4 class="fw-bold text-danger mb-0">
                        Rp {{ number_format($supplierPo->effective_outstanding_amount, 0, ',', '.') }}
                    </h4>
                </div>

                <hr>

                @if($supplierPo->effective_outstanding_amount > 0 && $supplierPo->payment_status !== 'paid')
                    <button type="button" 
                            class="btn btn-danger w-100 mb-2 py-2 fw-semibold"
                            data-bs-toggle="modal" 
                            data-bs-target="#paymentModal">
                        <i class="feather icon-check-circle me-1"></i> Catat Pelunasan Hutang
                    </button>
                @else
                    <div class="alert alert-success py-2 text-center fw-semibold mb-2">
                        <i class="feather icon-check me-1"></i> Tagihan Sudah Lunas
                    </div>
                @endif

                @if($supplierPo->status === 'received')
                    <a href="{{ route('supplier-po.invoice', $supplierPo->id) }}" 
                       target="_blank" 
                       class="btn btn-outline-success w-100 mb-2">
                        <i class="feather icon-file-text me-1"></i> Unduh Faktur Pembelian
                    </a>
                @endif

                <a href="{{ route('finance.ap') }}" class="btn btn-light w-100">
                    <i class="feather icon-arrow-left me-1"></i> Kembali ke Daftar Hutang
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.payment.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="ap">
            <input type="hidden" name="id" value="{{ $supplierPo->id }}">
            
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white fw-bold" id="paymentModalLabel">Catat Pelunasan Hutang ke Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-1">Supplier</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" value="{{ $supplierPo->supplier->name ?? '-' }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted mb-1">No. Invoice / No. PO</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" value="{{ $supplierPo->reference_number ?: $supplierPo->po_number }}" readonly>
                </div>
                <div class="row mb-3 bg-light p-2 rounded">
                    <div class="col-6">
                        <label class="form-label text-muted mb-0 small">Total Tagihan</label>
                        <div class="fw-bold text-muted">Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted mb-0 small">Sisa Hutang</label>
                        <div class="fw-bold text-danger">Rp {{ number_format($supplierPo->effective_outstanding_amount, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" min="0.01" max="{{ $supplierPo->effective_outstanding_amount }}" step="0.01" value="{{ $supplierPo->effective_outstanding_amount }}" required>
                    <small class="text-muted">Nominal default terisi sisa hutang. Dapat diubah jika membayar sebagian.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai / Kas</option>
                        <option value="cheque">Bilyet Giro / Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bank / Rekening Pengirim (Opsional)</label>
                    <input type="text" name="bank_name" class="form-control" placeholder="Contoh: Rekening BCA Operasional">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">No. Bukti Transfer / No. Referensi / No. Giro</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="Contoh: TRF-20261103-01 atau No. Bilyet Giro">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pembayaran hutang..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>
@endsection

