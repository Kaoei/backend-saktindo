@extends('layouts.dashboard',
[
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

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">

                    <table id="receivable-table"
                           class="table table-hover align-middle">

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
                                <th width="120" class="text-end">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($invoices as $invoice)

                                <tr>

                                    <td>
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td>
                                        {{ $invoice->salesOrder->customer_name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                                    </td>

                                    <td>
                                        {{ $invoice->due_date
                                            ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($invoice->grand_total,0,',','.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($invoice->paid_amount,0,',','.') }}
                                    </td>

                                    <td>
                                        <strong class="text-danger">
                                            Rp {{ number_format($invoice->outstanding_amount,0,',','.') }}
                                        </strong>
                                    </td>

                                    <td>

                                        @if($invoice->status == 'paid')
                                            <span class="badge bg-success">
                                                Lunas
                                            </span>

                                        @elseif($invoice->status == 'outstanding')
                                            <span class="badge bg-warning">
                                                Outstanding
                                            </span>

                                        @else
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        @endif

                                    </td>

                                    <td class="text-end">

                                        <a href="{{ route('finance.show', $invoice->id) }}"
                                           class="text-primary me-2"
                                           title="Detail">

                                            <i class="feather icon-eye f-18"></i>

                                        </a>

                                        @if($invoice->status != 'paid')

                                            <a href="{{ route('finance.payment.form', $invoice->id) }}"
                                               class="text-success"
                                               title="Pelunasan">

                                                <i class="feather icon-credit-card f-18"></i>

                                            </a>

                                        @endif

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

});

</script>

@endpush