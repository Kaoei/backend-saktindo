@extends('layouts.dashboard', [
    'title' => 'Accounts Receivable (Piutang)',
    'pageTitle' => 'Accounts Receivable (Piutang)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.index').'">Finance</a></li><li class="breadcrumb-item">Piutang</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('status') }}
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
                            <td>{{ $invoice->salesOrder->customer_name ?? 'N/A' }}</td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                            <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                            <td class="text-end text-danger fw-bold">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($invoice->status === 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($invoice->status === 'partial')
                                    <span class="badge bg-warning text-dark">Sebagian</span>
                                @else
                                    <span class="badge bg-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($invoice->outstanding_amount > 0)
                                    <button type="button" class="btn btn-sm btn-primary btn-pay" 
                                            data-id="{{ $invoice->id }}" 
                                            data-number="{{ $invoice->invoice_number }}" 
                                            data-outstanding="{{ $invoice->outstanding_amount }}"
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
        <div class="p-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.payment') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="ar">
            <input type="hidden" name="id" id="modal-invoice-id">
            
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pelunasan Piutang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">No. Invoice</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0" id="modal-invoice-number" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Sisa Tagihan (Outstanding)</label>
                    <input type="text" class="form-control-plaintext text-danger fw-bold p-0" id="modal-invoice-outstanding" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pembayaran (Rp)</label>
                    <input type="number" name="amount" id="modal-payment-amount" class="form-control" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pembayaran</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai / Cash</option>
                        <option value="cheque">Giro / Cheque</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rekening Penerimaan</label>
                    <select name="receiving_account" class="form-select" required>
                        <option value="js">JS</option>
                        <option value="sjb">SJB</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pelunasan</button>
            </div>
        </form>
    </div>
</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const payButtons = document.querySelectorAll('.btn-pay');
        payButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const number = this.getAttribute('data-number');
                const outstanding = this.getAttribute('data-outstanding');

                document.getElementById('modal-invoice-id').value = id;
                document.getElementById('modal-invoice-number').value = number;
                document.getElementById('modal-invoice-outstanding').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(outstanding);
                document.getElementById('modal-payment-amount').value = outstanding;
            });
        });
    });
</script>
@endpush
