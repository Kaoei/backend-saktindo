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
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Rekening</th>
                                <th>Referensi</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_number }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                    </td>

                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>

                                    <td>{{ strtoupper($payment->receiving_account) }}</td>

                                    <td>{{ $payment->reference_number ?? '-' }}</td>

                                    <td class="text-end">
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
                       class="btn btn-success w-100">
                        Pelunasan Piutang
                    </a>
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

@endsection