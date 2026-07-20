@extends('layouts.dashboard', [
    'title' => 'Detail Supplier PO',
    'pageTitle' => 'Detail Supplier PO',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier-po.index').'">Supplier PO</a></li><li class="breadcrumb-item">Detail</li>',
])

@section('content')
<div class="row">
    <!-- PO Details -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Informasi PO</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless align-middle mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">No. PO</td>
                        <td class="fw-bold">{{ $supplierPo->po_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $supplierPo->supplier->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Order</td>
                        <td>{{ $supplierPo->order_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Amount</td>
                        <td class="fw-bold text-success">Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            @if($supplierPo->status === 'received')
                                <span class="badge bg-success">Received</span>
                            @elseif($supplierPo->status === 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @if($supplierPo->notes)
                        <tr>
                            <td class="text-muted">Catatan</td>
                            <td>{{ $supplierPo->notes }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- PO Items -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Daftar Barang PO</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang</th>
                                <th>SKU</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Unit</th>
                                <th class="text-end">Diskon</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplierPo->items as $item)
                                @php
                                    $lineTotal = $item->qty * $item->price * (1 - $item->discount / 100);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $item->supplierProduct->item_name ?? 'N/A' }}</td>
                                    <td>{{ $item->supplierProduct->sku ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($item->qty, 0) }}</td>
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ (float) $item->discount }}%</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($lineTotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
