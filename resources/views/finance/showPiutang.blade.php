@extends('layouts.dashboard',
[
    'title' => 'Detail Piutang',
    'pageTitle' => 'Detail Piutang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.receivables').'">Piutang Usaha</a></li><li class="breadcrumb-item">Detail Piutang</li>'
])

@section('content')

<div class="row">

    <div class="col-md-8">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Informasi Invoice</h5>
                <small class="text-muted">Detail tagihan customer</small>
            </div>

            <div class="card-body">

                <table class="table table-bordered">
                    <tr>
                        <th width="30%">No Invoice</th>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>

                    <tr>
                        <th>Customer</th>
                        <td>{{ $invoice->salesOrder->customer_name ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Sales Order</th>
                        <td>{{ $invoice->salesOrder->id ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Tanggal Invoice</th>
                        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
                    </tr>

                    <tr>
                        <th>Jatuh Tempo</th>
                        <td>
                            {{ $invoice->due_date
                                ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y')
                                : '-'
                            }}
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            @if($invoice->status == 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($invoice->status == 'outstanding')
                                <span class="badge bg-warning">Outstanding</span>
                            @else
                                <span class="badge bg-secondary">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>

            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-1">Riwayat Pembayaran</h5>
                <small class="text-muted">Daftar pembayaran yang sudah masuk</small>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No Payment</th>
                                <th>Tanggal Bayar</th>
                                <th>Metode</th>
                                <th>Detail Pembayaran / Giro</th>
                                <th>Rekening</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td class="fw-semibold">{{ $payment->payment_number }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                    </td>

                                    <td>
                                        <span class="badge bg-light-primary text-primary">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                                    </td>

                                    <td>
                                        @if($payment->method === 'giro')
                                            <div><strong>Bank:</strong> {{ $payment->bank_name ?? '-' }}</div>
                                            <div><small class="text-muted">No. Giro:</small> {{ $payment->giro_number ?? $payment->reference_number ?? '-' }}</div>
                                            <div><small class="text-muted">Jatuh Tempo:</small> {{ $payment->giro_due_date ? \Carbon\Carbon::parse($payment->giro_due_date)->format('d M Y') : '-' }}</div>
                                            <div class="mt-1">
                                                @if($payment->giro_status === 'cleared')
                                                    <span class="badge bg-success">Cair</span>
                                                @elseif($payment->giro_status === 'rejected')
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending (Belum Cair)</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">{{ $payment->reference_number ?: '-' }}</span>
                                        @endif
                                    </td>

                                    <td>{{ strtoupper($payment->receiving_account) }}</td>

                                    <td class="text-end fw-bold text-success">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        Belum ada pembayaran.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Ringkasan Piutang</h5>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <small class="text-muted">Total Tagihan</small>
                    <h5>
                        Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                    </h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Sudah Terbayar</small>
                    <h5 class="text-success">
                        Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                    </h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Sisa Piutang</small>
                    <h5 class="text-danger">
                        Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}
                    </h5>
                </div>

                <hr>

                @if($invoice->status != 'paid')
                    <a href="{{ route('finance.payment.form', $invoice->id) }}"
                       class="btn btn-success w-100 mb-2">
                        <i class="feather icon-check-circle me-1"></i> Pelunasan Piutang
                    </a>
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#sendInvoiceEmailModal" data-toggle="modal" data-target="#sendInvoiceEmailModal">
                        <i class="feather icon-mail me-1"></i> Kirim Tagihan by Email
                    </button>
                @else
                    <button class="btn btn-secondary w-100" disabled>
                        Invoice Sudah Lunas
                    </button>
                @endif

                <a href="{{ route('finance.receivables') }}"
                   class="btn btn-light w-100 mt-2">
                    Kembali
                </a>

            </div>
        </div>

    </div>

</div>

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
                        <input type="text" class="form-control" value="{{ $invoice->salesOrder->customer_name ?? '-' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Tujuan <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $invoice->salesOrder?->customer?->email ?? '' }}" placeholder="contoh: finance@perusahaan.com" required>
                        <small class="text-muted">Rincian invoice dan nomor rekening transfer akan dikirim ke alamat email ini.</small>
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
@endsection