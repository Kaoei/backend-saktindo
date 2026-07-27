@extends('layouts.dashboard', [
    'title' => 'Supplier Purchase Orders',
    'pageTitle' => 'Supplier Purchase Orders',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Supplier PO</li>',
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
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-semibold">Daftar Purchase Order ke Supplier</h5>
        <a href="{{ route('supplier-po.create') }}" class="btn btn-primary d-flex align-items-center">
            <span class="material-icons-two-tone text-white me-1">add</span>
            Buat PO Supplier
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Tgl Order</th>
                        <th class="text-end">Total Amount</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplierPos as $po)
                        <tr>
                            <td class="fw-semibold">{{ $po->po_number }}</td>
                            <td>{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $po->order_date->format('d/m/Y') }}</td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($po->status === 'received')
                                    <span class="badge bg-success">Received</span>
                                @elseif($po->status === 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('supplier-po.show', $po->id) }}" class="btn btn-sm btn-outline-info">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada Purchase Order Supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $supplierPos->links() }}
        </div>
    </div>
</div>
    </div>
</div>
@endsection
