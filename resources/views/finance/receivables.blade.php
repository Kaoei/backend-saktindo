@extends('layouts.dashboard', [
    'title' => 'Piutang Usaha',
    'pageTitle' => 'Piutang Usaha',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Finance</li><li class="breadcrumb-item">Piutang Usaha</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Outstanding</h6>
                <h4 class="text-warning">
                    {{ $outstandingCount }} Invoice
                </h4>
                <small>
                    Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Lewat Tempo</h6>
                <h4 class="text-danger">
                    {{ $overdueCount }} Invoice
                </h4>
                <small>
                    Rp {{ number_format($overdueAmount, 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Pemasukan</h6>
                <h4 class="text-success">
                    {{ $paymentCount }} Pembayaran
                </h4>
                <small>
                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Invoice Lunas</h6>
                <h4 class="text-primary">
                    {{ $paidCount }} Invoice
                </h4>
                <small>
                    Rp {{ number_format($paidAmount, 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Daftar Piutang Usaha</h5>
                    <small class="text-muted">
                        Daftar invoice customer dan status pembayarannya
                    </small>
                </div>
            </div>

            <div class="card-body">
                @if(session('success') || session('status'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') ?? session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="receivable-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No Invoice</th>
                                <th>Customer</th>
                                <th>Tanggal Invoice</th>
                                <th>Jatuh Tempo</th>
                                <th>Total Tagihan</th>
                                <th>Terbayar</th>
                                <th>Sisa Piutang</th>
                                <th>Status</th>
                                <th width="180" class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $invoice->invoice_number }}
                                    </td>
                                    <td>
                                        {{ $invoice->salesOrder->customer_name ?? '-' }}
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                                    </td>
                                    <td>
                                        {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td>
                                        Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-success">
                                        Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <strong class="text-danger">
                                            Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($invoice->status == 'paid')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($invoice->status == 'outstanding')
                                            <span class="badge bg-warning text-dark">Outstanding</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('finance.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary" title="Detail Invoice">
                                                <i class="feather icon-eye"></i>
                                            </a>

                                            @if($invoice->status != 'paid' && $invoice->outstanding_amount > 0)
                                                <button type="button" class="btn btn-sm btn-success btn-pay text-white"
                                                    data-id="{{ $invoice->id }}"
                                                    data-number="{{ $invoice->invoice_number }}"
                                                    data-customer="{{ $invoice->salesOrder->customer_name ?? '-' }}"
                                                    data-outstanding="{{ $invoice->outstanding_amount }}"
                                                    data-action="{{ route('finance.payment.store', $invoice->id) }}"
                                                    data-toggle="modal"
                                                    data-target="#paymentModal"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#paymentModal"
                                                    title="Pelunasan Piutang">
                                                    <i class="feather icon-credit-card me-1"></i>Pelunasan
                                                </button>
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
    </div>
</div>

<!-- Modal Pelunasan Piutang -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentModalForm" action="" method="POST" class="modal-content">
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
                    <label class="form-label text-muted mb-0">Sisa Piutang (Outstanding)</label>
                    <input type="text" class="form-control-plaintext text-danger fw-bold p-0 fs-5" id="modal-invoice-outstanding-display" readonly>
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
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pelunasan"></textarea>
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
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {
    $('#receivable-table').DataTable({
        pageLength: 25,
        order: [[2, 'desc']],
        language: {
            emptyTable: 'Belum ada data piutang usaha.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [8]
            }
        ]
    });

    $(document).on('click', '.btn-pay', function (e) {
        e.preventDefault();
        const action = $(this).data('action');
        const number = $(this).data('number');
        const customer = $(this).data('customer') || '-';
        const outstanding = parseFloat($(this).data('outstanding')) || 0;

        $('#paymentModalForm').attr('action', action);
        $('#modal-invoice-number').val(number);
        $('#modal-customer-name').val(customer);
        $('#modal-invoice-outstanding-display').val('Rp ' + new Intl.NumberFormat('id-ID').format(outstanding));
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