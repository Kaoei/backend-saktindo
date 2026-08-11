@extends('layouts.dashboard', [
    'title' => 'Finance Overview',
    'pageTitle' => 'Finance Overview',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Finance</li>',
])

@section('content')
<div class="row mb-4">
    <!-- Piutang Card -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-gradient-info text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase mb-2">Total Piutang (Receivables)</h6>
                        <h3 class="fw-bold mb-0">Rp {{ number_format($totalAR, 0, ',', '.') }}</h3>
                    </div>
                    <div class="bg-white-20 p-3 rounded-circle">
                        <span class="material-icons-two-tone text-white" style="font-size: 3rem;">account_balance_wallet</span>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('finance.ar') }}" class="btn btn-sm btn-light text-primary">Lihat Detail Piutang</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hutang Card -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm bg-gradient-danger text-white" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase mb-2">Total Hutang (Payables)</h6>
                        <h3 class="fw-bold mb-0">Rp {{ number_format($totalAP, 0, ',', '.') }}</h3>
                    </div>
                    <div class="bg-white-20 p-3 rounded-circle">
                        <span class="material-icons-two-tone text-white" style="font-size: 3rem;">payment</span>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('finance.ap') }}" class="btn btn-sm btn-light text-danger">Lihat Detail Hutang</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Collections (AR) -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Penerimaan Terbaru (Piutang)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td class="fw-semibold">{{ $payment->invoice->invoice_number }}</td>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-secondary">{{ strtoupper($payment->payment_method) }}</span></td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada catatan penerimaan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments (AP) -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Pembayaran Terbaru (Hutang)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice Supplier</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAPPayments as $payment)
                                <tr>
                                    <td class="fw-semibold">{{ $payment->invoice_number }}</td>
                                    <td>{{ $payment->purchase_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-success">Lunas</span></td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada catatan pembayaran hutang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-white-20 {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>
@endpush
