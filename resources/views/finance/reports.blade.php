@extends('layouts.dashboard',
[
    'title' => 'Laporan Keuangan',
    'pageTitle' => 'Laporan Keuangan',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Finance</li><li class="breadcrumb-item">Laporan Keuangan</li>'
])

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="row">

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Penjualan</h6>
                <h4>Rp {{ number_format($totalSales, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Pembayaran Masuk</h6>
                <h4 class="text-success">
                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Piutang</h6>
                <h4 class="text-warning">
                    Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Invoice Outstanding</h6>
                <h4>{{ $outstandingInvoices }}</h4>
            </div>
        </div>
    </div>

</div>

<div class="row mt-3">
    <div class="col-12">

        <div class="card">

            <div class="card-header">
                <h5 class="mb-1">Ringkasan Laporan Finance</h5>
                <small class="text-muted">
                    Laporan berdasarkan invoice dan pembayaran customer
                </small>
            </div>

            <div class="card-body">

                <table id="report-table" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Total nilai penjualan</td>
                            <td class="text-end">
                                Rp {{ number_format($totalSales, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <td>Total penerimaan kas dari pelanggan</td>
                            <td class="text-end text-success">
                                Rp {{ number_format($totalPaid, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <td>Saldo piutang outstanding</td>
                            <td class="text-end text-warning">
                                Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <td>Jumlah invoice lunas</td>
                            <td class="text-end">
                                {{ $paidInvoices }}
                            </td>
                        </tr>

                        <tr>
                            <td>Jumlah invoice belum lunas</td>
                            <td class="text-end">
                                {{ $outstandingInvoices }}
                            </td>
                        </tr>
                    </tbody>
                </table>

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
    $('#report-table').DataTable({
        pageLength: 10,
        searching: false,
        paging: false,
        info: false,
        language: {
            emptyTable: 'Belum ada data laporan.'
        }
    });
});
</script>

@endpush