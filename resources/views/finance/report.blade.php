@extends('layouts.dashboard', [
    'title' => 'Finance Report',
    'pageTitle' => 'Finance Report',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.index').'">Finance</a></li><li class="breadcrumb-item">Report</li>',
])

@section('content')
<div class="row">
    <!-- AR Monthly Report -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Laporan Piutang Bulanan (AR)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bulan</th>
                                <th class="text-end">Total Penjualan</th>
                                <th class="text-end">Total Tertagih</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($arMonthly as $ar)
                                <tr>
                                    <td class="fw-semibold">{{ $ar->month }}</td>
                                    <td class="text-end">Rp {{ number_format($ar->total_billing, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($ar->total_collected, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($ar->total_billing - $ar->total_collected, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada data piutang bulanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- AP Monthly Report -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Laporan Hutang Bulanan (AP)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bulan</th>
                                <th class="text-end">Total Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($apMonthly as $ap)
                                <tr>
                                    <td class="fw-semibold">{{ $ap->month }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($ap->total_purchase, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Belum ada data hutang bulanan.</td>
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
