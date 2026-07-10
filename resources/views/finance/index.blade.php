@extends('layouts.dashboard',
[
    'title' => 'Finance Dashboard',
    'pageTitle' => 'Finance Dashboard',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Finance</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="row">

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Total Invoice</h6>
                <h4 class="mb-0">
                    Rp {{ number_format($totalInvoice, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Total Piutang</h6>
                <h4 class="mb-0 text-warning">
                    Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Total Pelunasan</h6>
                <h4 class="mb-0 text-success">
                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Invoice Lunas</h6>
                <h4 class="mb-0">
                    {{ $paidInvoices }}
                </h4>
            </div>
        </div>
    </div>

</div>

<div class="row mt-3">
    <div class="col-12">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Invoice Terbaru</h5>
                    <small class="text-muted">
                        Ringkasan invoice dan status piutang customer
                    </small>
                </div>

                <a href="{{ route('finance.receivables') }}" class="btn btn-primary btn-sm">
                    Lihat Piutang
                </a>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table id="invoice-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No Invoice</th>
                                <th>Customer</th>
                                <th>Tanggal Invoice</th>
                                <th>Total Tagihan</th>
                                <th>Terbayar</th>
                                <th>Sisa Piutang</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>

                                    <td>{{ $invoice->salesOrder->customer_name ?? '-' }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}
                                    </td>

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
    $('#invoice-table').DataTable({
        pageLength: 25,
        order: [[2, 'desc']],
        language: {
            emptyTable: 'Belum ada data invoice.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [6]
            }
        ]
    });
});
</script>

@endpush