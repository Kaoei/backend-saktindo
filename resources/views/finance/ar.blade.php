@extends('layouts.dashboard', [
    'title' => 'Accounts Receivable (Piutang)',
    'pageTitle' => 'Accounts Receivable (Piutang)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.index').'">Finance</a></li><li class="breadcrumb-item">Piutang</li>',
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

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Daftar Tagihan Piutang Customer</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Invoice</th>
                                <th>Customer</th>
                                <th>Tgl Invoice</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-end">Dibayar</th>
                                <th class="text-end">Outstanding</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->salesOrder->customer_name ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                                    <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                                    <td>
                                        @if($invoice->status === 'paid')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($invoice->status === 'outstanding')
                                            <span class="badge bg-warning text-dark">Outstanding</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($invoice->status !== 'paid' && $invoice->outstanding_amount > 0)
                                            <button type="button" class="btn btn-sm btn-success btn-pay text-white" 
                                                    data-id="{{ $invoice->id }}" 
                                                    data-number="{{ $invoice->invoice_number }}" 
                                                    data-customer="{{ $invoice->salesOrder->customer_name ?? '-' }}"
                                                    data-outstanding="{{ $invoice->outstanding_amount }}"
                                                    data-action="{{ route('finance.payment.store', $invoice->id) }}"
                                                    data-toggle="modal"
                                                    data-target="#paymentModal"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal">
                                                Pelunasan
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Lunas</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data piutang ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($invoices, 'links'))
                    <div class="p-3">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="arPaymentModalForm" action="" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pelunasan Piutang</h5>
                <button type="button" class="btn-close close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-0">No. Invoice</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-invoice-number" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted mb-0">Customer</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-customer-name" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted mb-0">Sisa Tagihan (Outstanding)</label>
                    <input type="text" class="form-control-plaintext text-danger fw-bold p-0 fs-5" id="modal-invoice-outstanding" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="method" class="form-select" required>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="cash">Tunai / Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="giro">Giro / Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rekening Penerimaan <span class="text-danger">*</span></label>
                    <select name="receiving_account" class="form-select" required>
                        <option value="js">JS</option>
                        <option value="sjb">SJB</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="modal-payment-amount" class="form-control" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Referensi (Opsional)</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="Contoh: No. transfer / giro">
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan Pelunasan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $(document).on('click', '.btn-pay', function (e) {
            e.preventDefault();
            const action = $(this).data('action');
            const number = $(this).data('number');
            const customer = $(this).data('customer') || '-';
            const outstanding = parseFloat($(this).data('outstanding')) || 0;

            $('#arPaymentModalForm').attr('action', action);
            $('#modal-invoice-number').val(number);
            $('#modal-customer-name').val(customer);
            $('#modal-invoice-outstanding').val('Rp ' + new Intl.NumberFormat('id-ID').format(outstanding));
            $('#modal-payment-amount').val(outstanding).attr('max', outstanding);

            if (typeof $.fn.modal !== 'undefined') {
                $('#paymentModal').modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }
        });
    });
</script>
@endpush
