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
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold">Informasi PO</h5>
                @if($supplierPo->status !== 'cancelled')
                    <a href="{{ route('supplier-po.edit', $supplierPo->id) }}" class="btn btn-sm btn-outline-warning">
                        <i class="feather icon-edit me-1"></i> Edit PO
                    </a>
                @endif
            </div>
            <div class="card-body">
                <table class="table table-borderless align-middle mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">No. PO</td>
                        <td class="fw-bold">{{ $supplierPo->po_number }}</td>
                    </tr>
                    @if($supplierPo->reference_number)
                    <tr>
                        <td class="text-muted">No. Referensi</td>
                        <td><span class="badge bg-light text-dark border">{{ $supplierPo->reference_number }}</span></td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $supplierPo->supplier->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Order</td>
                        <td>{{ $supplierPo->order_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Subtotal Barang</td>
                        <td class="fw-semibold">Rp {{ number_format($supplierPo->subtotal > 0 ? $supplierPo->subtotal : $supplierPo->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if((float)($supplierPo->additional_discount ?? 0) > 0)
                    <tr>
                        <td class="text-muted">Diskon Tambahan</td>
                        <td class="fw-semibold text-danger">- Rp {{ number_format($supplierPo->additional_discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Pajak</td>
                        <td>
                            @if($supplierPo->tax_type === 'pajak' || (float)($supplierPo->tax_amount ?? 0) > 0)
                                <span class="badge bg-light-primary text-primary">Pajak (PPN)</span>
                                <span class="fw-semibold text-dark ms-1">Rp {{ number_format($supplierPo->tax_amount, 0, ',', '.') }}</span>
                            @else
                                <span class="badge bg-light text-muted border">Non Pajak</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Grand Total</td>
                        <td class="fw-bold text-success fs-5">Rp {{ number_format($supplierPo->total_amount, 0, ',', '.') }}</td>
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
                                <th class="text-center">Diskon</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplierPo->items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->supplierProduct->item_name ?? 'N/A' }}</td>
                                    <td><code>{{ $item->supplierProduct->sku ?? 'N/A' }}</code></td>
                                    <td class="text-end fw-bold">{{ number_format($item->qty, 0) }}</td>
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark font-monospace">{{ $item->formatted_discount }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
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
