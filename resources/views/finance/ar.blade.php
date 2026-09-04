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
                                        <div class="d-flex justify-content-center gap-1">
                                            @if($invoice->status !== 'paid' && $invoice->outstanding_amount > 0)
                                                <button type="button" class="btn btn-sm btn-success btn-pay text-white" 
                                                        data-id="{{ $invoice->id }}" 
                                                        data-number="{{ $invoice->invoice_number }}" 
                                                        data-customer="{{ $invoice->salesOrder->customer_name ?? '-' }}"
                                                        data-due-date="{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}"
                                                        data-outstanding="{{ $invoice->outstanding_amount }}"
                                                        data-action="{{ route('finance.payment.store', $invoice->id) }}"
                                                        data-toggle="modal"
                                                        data-target="#paymentModal"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#paymentModal">
                                                    Pelunasan
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-send-ar-email"
                                                        data-id="{{ $invoice->id }}"
                                                        data-number="{{ $invoice->invoice_number }}"
                                                        data-customer="{{ $invoice->salesOrder->customer_name ?? '-' }}"
                                                        data-email="{{ $invoice->salesOrder?->customer?->email ?? '' }}"
                                                        data-action="{{ route('finance.invoices.send-email', $invoice->id) }}"
                                                        title="Kirim Tagihan by Email">
                                                    <i class="feather icon-mail"></i> Email
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>Lunas</button>
                                            @endif
                                        </div>
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
                    <div class="d-flex justify-content-end mt-3">
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
            <input type="hidden" name="type" value="ar">
            <input type="hidden" name="id" id="modal-invoice-id" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pelunasan Piutang</h5>
                <button type="button" class="btn-close close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-0">No. Invoice</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-invoice-number" readonly>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted mb-0">Nama Customer</label>
                        <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-customer-name" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted mb-0">Jatuh Tempo</label>
                        <input type="text" class="form-control-plaintext fw-bold p-0 text-dark" id="modal-due-date" readonly>
                    </div>
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
                    <select name="method" id="ar-modal-payment-method" class="form-select" required>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="cash">Tunai / Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="giro">Giro / Cheque</option>
                    </select>
                </div>

                <!-- Detail Giro Container -->
                <div id="ar-modal-giro-fields" class="card bg-light border p-3 mb-3" style="display: none;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="feather icon-credit-card text-primary me-2"></i>
                        <h6 class="mb-0 fw-bold text-primary">Detail Warkat Giro</h6>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-semibold">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="ar-modal-giro-bank" class="form-control form-control-sm" placeholder="Contoh: BCA / Mandiri / BRI">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-semibold">No. Bilyet Giro <span class="text-danger">*</span></label>
                            <input type="text" name="giro_number" id="ar-modal-giro-number" class="form-control form-control-sm" placeholder="Nomor Bilyet Giro">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-semibold">Tgl Jatuh Tempo Giro <span class="text-danger">*</span></label>
                            <input type="date" name="giro_due_date" id="ar-modal-giro-due-date" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-semibold">Status Giro <span class="text-danger">*</span></label>
                            <select name="giro_status" id="ar-modal-giro-status" class="form-select form-select-sm">
                                <option value="pending">Pending (Menunggu Jatuh Tempo)</option>
                                <option value="cleared">Cleared (Langsung Cair)</option>
                                <option value="rejected">Rejected (Ditolak)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama & Nomor Bank / Rekening Penerimaan <span class="text-danger">*</span></label>
                    <select name="receiving_account" class="form-select" required>
                        <option value="js">JS (BCA / Bank Rekening JS)</option>
                        <option value="sjb">SJB (Mandiri / Bank Rekening SJB)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Referensi (Opsional)</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="Contoh: No. Transfer Bank / Bukti Setor">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="modal-payment-amount" class="form-control" min="0.01" step="0.01" required>
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
<!-- Modal Kirim Email Tagihan AR -->
<div class="modal fade" id="sendInvoiceEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="formSendInvoiceEmail" action="">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold"><i class="feather icon-mail me-1"></i> Kirim Ulang Tagihan by Email</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Invoice</label>
                        <input type="text" class="form-control" id="emailModalInvoiceNumber" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer</label>
                        <input type="text" class="form-control" id="emailModalCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Tujuan <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" id="emailModalRecipient" placeholder="contoh: finance@perusahaan.com" required>
                        <small class="text-muted">Tagihan invoice dan rekening pembayaran akan dikirim ke email ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pesan Tambahan (Opsional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Pesan khusus untuk client"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="feather icon-send me-1"></i> Kirim Email Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        function toggleArGiro() {
            if ($('#ar-modal-payment-method').val() === 'giro') {
                $('#ar-modal-giro-fields').slideDown(200);
                $('#ar-modal-giro-bank, #ar-modal-giro-number, #ar-modal-giro-due-date').prop('required', true);
            } else {
                $('#ar-modal-giro-fields').slideUp(200);
                $('#ar-modal-giro-bank, #ar-modal-giro-number, #ar-modal-giro-due-date').prop('required', false);
            }
        }

        $('#ar-modal-payment-method').on('change', toggleArGiro);

        $(document).on('click', '.btn-pay', function (e) {
            e.preventDefault();
            const action = $(this).data('action');
            const id = $(this).data('id');
            const number = $(this).data('number');
            const customer = $(this).data('customer') || '-';
            const dueDate = $(this).data('due-date') || '-';
            const outstanding = parseFloat($(this).data('outstanding')) || 0;

            $('#arPaymentModalForm').attr('action', action);
            $('#modal-invoice-id').val(id);
            $('#modal-invoice-number').val(number);
            $('#modal-customer-name').val(customer);
            $('#modal-due-date').val(dueDate);
            $('#modal-invoice-outstanding').val('Rp ' + new Intl.NumberFormat('id-ID').format(outstanding));
            $('#modal-payment-amount').val(outstanding).attr('max', outstanding);

            $('#ar-modal-payment-method').val('transfer_bank');
            toggleArGiro();

            if (typeof $.fn.modal !== 'undefined') {
                $('#paymentModal').modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }
        });

        $(document).on('click', '.btn-send-ar-email', function (e) {
            e.preventDefault();
            const number = $(this).data('number');
            const customer = $(this).data('customer') || '-';
            const email = $(this).data('email') || '';
            const action = $(this).data('action');

            $('#emailModalInvoiceNumber').val(number);
            $('#emailModalCustomerName').val(customer);
            $('#emailModalRecipient').val(email);
            $('#formSendInvoiceEmail').attr('action', action);

            if (typeof $.fn.modal !== 'undefined') {
                $('#sendInvoiceEmailModal').modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(document.getElementById('sendInvoiceEmailModal')).show();
            }
        });
    });
</script>
@endpush
