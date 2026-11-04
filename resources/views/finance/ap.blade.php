@extends('layouts.dashboard', [
    'title' => 'Accounts Payable (Hutang)',
    'pageTitle' => 'Accounts Payable (Hutang)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.index').'">Finance</a></li><li class="breadcrumb-item">Hutang</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status') || session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="feather icon-check-circle me-1"></i> {{ session('status') ?? session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="feather icon-alert-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="feather icon-alert-circle me-1"></i> Terjadi kesalahan pengisian form:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 fw-semibold">Daftar Tagihan Hutang ke Supplier</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier-po.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="feather icon-list me-1"></i> Data PO Supplier
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ap-table" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice Supplier</th>
                                <th>Supplier</th>
                                <th>Barang</th>
                                <th>Qty</th>
                                <th>Tgl Pembelian</th>
                                <th class="text-end">Total Tagihan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                @php
                                    $isPo = $purchase instanceof \App\Models\SupplierPO;
                                    $invNumber = $isPo ? ($purchase->reference_number ?: $purchase->po_number) : ($purchase->invoice_number ?: '-');
                                    $poNumber = $isPo ? $purchase->po_number : null;
                                    $supplierName = $purchase->supplier->name ?? 'N/A';
                                    
                                    if ($isPo) {
                                        $items = $purchase->items ?? collect();
                                        $itemCount = $items->count();
                                        $firstItemName = $items->first()?->supplierProduct?->item_name ?? 'Barang PO';
                                        $barangLabel = $itemCount > 1 ? $firstItemName . ' (+' . ($itemCount - 1) . ' item lainnya)' : $firstItemName;
                                        $qtyTotal = $items->sum('qty');
                                        $tgl = $purchase->order_date ? \Carbon\Carbon::parse($purchase->order_date)->format('d/m/Y') : '-';
                                        $dueDate = $purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date)->format('d/m/Y') : null;
                                        $totalTagihan = (float) $purchase->total_amount;
                                        $paid = (float) ($purchase->paid_amount ?? 0);
                                        $outstanding = $purchase->outstanding_amount !== null ? (float) $purchase->outstanding_amount : max(0, $totalTagihan - $paid);
                                        $payStatus = $purchase->payment_status ?: ($paid >= $totalTagihan && $totalTagihan > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'));
                                    } else {
                                        $barangLabel = $purchase->item_name ?? '-';
                                        $qtyTotal = $purchase->quantity ?? 1;
                                        $tgl = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') : '-';
                                        $dueDate = null;
                                        $totalTagihan = (float) $purchase->total_amount;
                                        $paid = $purchase->status === 'paid' ? $totalTagihan : 0;
                                        $outstanding = $purchase->status === 'paid' ? 0 : $totalTagihan;
                                        $payStatus = $purchase->status === 'paid' ? 'paid' : 'unpaid';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $invNumber }}</span>
                                        @if($poNumber && $poNumber !== $invNumber)
                                            <div><span class="badge bg-light text-muted border">PO: {{ $poNumber }}</span></div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $supplierName }}</div>
                                        @if($purchase->supplier?->company_name && $purchase->supplier->company_name !== $supplierName)
                                            <small class="text-muted d-block">{{ $purchase->supplier->company_name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span>{{ $barangLabel }}</span>
                                    </td>
                                    <td>{{ number_format($qtyTotal, 0, ',', '.') }}</td>
                                    <td>
                                        <div>{{ $tgl }}</div>
                                        @if($dueDate)
                                            <small class="text-danger d-block">Tempo: {{ $dueDate }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                                        @if($paid > 0 && $payStatus !== 'paid')
                                            <small class="text-success d-block">Dibayar: Rp {{ number_format($paid, 0, ',', '.') }}</small>
                                            <small class="text-danger fw-semibold d-block">Sisa: Rp {{ number_format($outstanding, 0, ',', '.') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payStatus === 'paid')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($payStatus === 'partial')
                                            <span class="badge bg-warning text-dark">Sebagian</span>
                                        @else
                                            <span class="badge bg-danger">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if($payStatus !== 'paid' && $outstanding > 0)
                                                <button type="button" class="btn btn-sm btn-danger btn-pay text-white" 
                                                        data-id="{{ $purchase->id }}" 
                                                        data-number="{{ $invNumber }}"
                                                        data-supplier="{{ $supplierName }}"
                                                        data-total="{{ $totalTagihan }}"
                                                        data-outstanding="{{ $outstanding }}"
                                                        data-toggle="modal"
                                                        data-target="#paymentModal"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#paymentModal">
                                                    <i class="feather icon-credit-card me-1"></i> Bayar Tagihan
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>Lunas</button>
                                            @endif
                                            @if($isPo)
                                                <a href="{{ route('finance.ap.show', $purchase->id) }}" class="btn btn-sm btn-outline-info" title="Detail Hutang & Riwayat Pembayaran">
                                                    Detail
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('finance.payment.store') }}" method="POST" class="modal-content" id="formPaymentModal">
                    @csrf
                    <input type="hidden" name="type" value="ap">
                    <input type="hidden" name="id" id="modal-purchase-id">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white fw-bold" id="paymentModalLabel">Catat Pelunasan Hutang ke Supplier</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted mb-1">Supplier</label>
                            <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-supplier-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted mb-1">No. Invoice / No. PO</label>
                            <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-invoice-number" readonly>
                        </div>
                        <div class="row mb-3 bg-light p-2 rounded">
                            <div class="col-6">
                                <label class="form-label text-muted mb-0 small">Total Tagihan</label>
                                <input type="text" class="form-control-plaintext text-muted fw-bold p-0" id="modal-invoice-total" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-0 small">Sisa Hutang</label>
                                <input type="text" class="form-control-plaintext text-danger fw-bold p-0" id="modal-invoice-outstanding" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="modal-payment-amount" class="form-control" min="0.01" step="0.01" required>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="btnSubmitPayment">
                            <i class="feather icon-check me-1"></i> Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#ap-table').DataTable({
                pageLength: 10,
                order: [[4, 'desc']],
                language: { emptyTable: 'Tidak ada data hutang ditemukan.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [7] }
                ]
            });

            $(document).on('click', '.btn-pay', function(e) {
                e.preventDefault();
                const id = $(this).data('id') || $(this).attr('data-id');
                const number = $(this).data('number') || $(this).attr('data-number');
                const supplier = $(this).data('supplier') || $(this).attr('data-supplier');
                const total = parseFloat($(this).data('total') || $(this).attr('data-total')) || 0;
                const outstanding = parseFloat($(this).data('outstanding') || $(this).attr('data-outstanding')) || total;

                $('#modal-purchase-id').val(id);
                $('#modal-supplier-name').val(supplier || '-');
                $('#modal-invoice-number').val(number);
                $('#modal-invoice-total').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
                $('#modal-invoice-outstanding').val('Rp ' + new Intl.NumberFormat('id-ID').format(outstanding));
                
                const amountInput = $('#modal-payment-amount');
                amountInput.val(outstanding);
                amountInput.attr('max', outstanding);

                // Show modal explicitly for compatibility across Bootstrap versions
                if (typeof $.fn.modal !== 'undefined') {
                    $('#paymentModal').modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    new bootstrap.Modal(document.getElementById('paymentModal')).show();
                }
            });

            $('#formPaymentModal').on('submit', function() {
                const btn = $('#btnSubmitPayment');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...');
            });
        });
    </script>
@endpush
