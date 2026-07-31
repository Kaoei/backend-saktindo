@extends('layouts.dashboard', [
    'title' => 'Accounts Payable (Hutang)',
    'pageTitle' => 'Accounts Payable (Hutang)',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.index').'">Finance</a></li><li class="breadcrumb-item">Hutang</li>',
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
        <h5 class="card-title mb-0 fw-semibold">Daftar Tagihan Hutang ke Supplier</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                        <tr>
                            <td class="fw-semibold">{{ $purchase->invoice_number }}</td>
                            <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $purchase->item_name }}</td>
                            <td>{{ number_format($purchase->quantity, 0) }}</td>
                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td class="text-end fw-bold text-danger">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($purchase->status === 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @else
                                    <span class="badge bg-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($purchase->status !== 'paid')
                                    <button type="button" class="btn btn-sm btn-danger btn-pay" 
                                            data-id="{{ $purchase->id }}" 
                                            data-number="{{ $purchase->invoice_number }}" 
                                            data-total="{{ $purchase->total_amount }}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#paymentModal">
                                        Bayar Tagihan
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Lunas</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada data hutang ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $purchases->links() }}
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.payment.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="ap">
            <input type="hidden" name="id" id="modal-purchase-id">
            
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pelunasan Hutang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">No. Invoice Supplier</label>
                    <input type="text" class="form-control-plaintext fw-bold p-0" id="modal-invoice-number" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Jumlah Tagihan</label>
                    <input type="text" class="form-control-plaintext text-danger fw-bold p-0" id="modal-invoice-total" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pembayaran (Rp)</label>
                    <input type="number" name="amount" id="modal-payment-amount" class="form-control" min="0.01" step="0.01" readonly required>
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
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
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
                const total = this.getAttribute('data-total');

                document.getElementById('modal-purchase-id').value = id;
                document.getElementById('modal-invoice-number').value = number;
                document.getElementById('modal-invoice-total').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
                document.getElementById('modal-payment-amount').value = total;
            });
        });
    });
</script>
@endpush
