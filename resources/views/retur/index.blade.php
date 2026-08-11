@extends('layouts.dashboard', [
    'title' => 'Retur Barang',
    'pageTitle' => 'Retur Barang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Retur Barang</li>',
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

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Daftar Retur Barang dari Customer</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Retur</th>
                                <th>No. Sales Order</th>
                                <th>Customer</th>
                                <th>Tgl Retur</th>
                                <th>Tgl Kembali</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($returs as $retur)
                                <tr>
                                    <td class="fw-semibold">{{ $retur->id }}</td>
                                    <td>
                                        <a href="{{ route('sales-finance.show', $retur->sales_order_id) }}">
                                            {{ $retur->sales_order_id }}
                                        </a>
                                    </td>
                                    <td>{{ $retur->salesOrder->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $retur->return_date->format('d/m/Y') }}</td>
                                    <td>{{ $retur->received_date ? $retur->received_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($retur->status === 'received')
                                            <span class="badge bg-success">Received (Kembali)</span>
                                        @elseif($retur->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $retur->notes ?: '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('sales-finance.show', $retur->sales_order_id) }}" class="btn btn-sm btn-outline-primary">
                                            Detail & Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data retur barang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $returs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
