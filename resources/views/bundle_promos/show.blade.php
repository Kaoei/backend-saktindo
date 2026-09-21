@extends('layouts.dashboard', [
    'title' => 'Detail Promo Bundling',
    'pageTitle' => 'Detail Promo Bundling',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('bundle-promos.index').'">Promo Bundling</a></li><li class="breadcrumb-item">Detail</li>',
])

@section('content')

<div class="row mt-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <span class="badge bg-light-primary text-primary font-monospace fw-bold mb-1">{{ $bundlePromo->bundle_code }}</span>
                    <h4 class="mb-0 fw-bold text-dark">{{ $bundlePromo->name }}</h4>
                </div>
                <div>
                    @if($bundlePromo->status === 'active')
                        <span class="badge bg-success fs-6">Aktif</span>
                    @else
                        <span class="badge bg-secondary fs-6">Non-Aktif</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($bundlePromo->description)
                    <p class="text-muted mb-4">{{ $bundlePromo->description }}</p>
                @endif

                <h6 class="fw-bold text-dark mb-3">Daftar Komponen Produk dalam 1 Paket:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NAMA BARANG</th>
                                <th>SKU</th>
                                <th class="text-center" style="width: 120px;">QTY / PAKET</th>
                                <th class="text-end" style="width: 150px;">HARGA NORMAL</th>
                                <th class="text-end" style="width: 150px;">SUBTOTAL NORMAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bundlePromo->items as $item)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $item->supplierProduct->item_name ?? '-' }}</td>
                                    <td><span class="badge bg-light text-secondary border">{{ $item->supplierProduct->sku ?? '-' }}</span></td>
                                    <td class="text-center fw-bold">{{ $item->qty }} pcs</td>
                                    <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total Harga Normal Komponen:</td>
                                <td class="text-end fw-bold">Rp {{ number_format($bundlePromo->original_price, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark">Ringkasan Promo</h5>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light-success rounded border border-success-subtle mb-4 text-center">
                    <span class="text-muted small d-block">Harga Promo Paket</span>
                    <h2 class="text-success fw-bold mb-1">Rp {{ number_format($bundlePromo->bundle_price, 0, ',', '.') }}</h2>
                    <span class="badge bg-danger">
                        Hemat Rp {{ number_format($bundlePromo->savings_amount, 0, ',', '.') }} ({{ $bundlePromo->savings_percentage }}%)
                    </span>
                </div>

                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Harga Normal:</span>
                        <span class="fw-semibold"><del>Rp {{ number_format($bundlePromo->original_price, 0, ',', '.') }}</del></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tipe Diskon:</span>
                        <span class="fw-semibold">{{ $bundlePromo->discount_type === 'percentage' ? 'Persentase' : 'Nominal Tetap' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Nilai Diskon:</span>
                        <span class="fw-semibold">{{ $bundlePromo->discount_type === 'percentage' ? $bundlePromo->discount_value . '%' : 'Rp ' . number_format($bundlePromo->discount_value, 0, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Masa Berlaku:</span>
                        <span class="fw-semibold">
                            {{ $bundlePromo->start_date ? $bundlePromo->start_date->format('d M Y') : 'Sekarang' }} &mdash; {{ $bundlePromo->end_date ? $bundlePromo->end_date->format('d M Y') : 'Selamanya' }}
                        </span>
                    </li>
                </ul>

                <div class="d-grid gap-2">
                    <a href="{{ route('bundle-promos.edit', $bundlePromo->id) }}" class="btn btn-outline-primary">
                        <i class="feather icon-edit me-1"></i> Edit Paket Promo
                    </a>
                    <a href="{{ route('bundle-promos.index') }}" class="btn btn-light">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
