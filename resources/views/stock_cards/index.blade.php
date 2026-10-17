@extends('layouts.dashboard',
[
    'title' => 'Kartu Stok',
    'pageTitle' => 'Kartu Stok Persediaan',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('gudang-product.index').'">Gudang</a></li><li class="breadcrumb-item">Kartu Stok</li>'
])

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .kpi-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .table-stock-card th {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        background-color: #f8fafc;
    }
    .table-stock-card td {
        font-size: 0.88rem;
        vertical-align: middle;
    }
    .badge-soft-success {
        background-color: #e8fadf;
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }
    .badge-soft-danger {
        background-color: #fde8e8;
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }
    .badge-soft-info {
        background-color: #e3f2fd;
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, 0.2);
    }
    .badge-soft-warning {
        background-color: #fff8e1;
        color: #ff9800;
        border: 1px solid rgba(255, 152, 0, 0.2);
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="feather icon-check-circle me-2"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="feather icon-alert-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Card Header & Navigation Actions -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-3 mb-3">
                    <div>
                        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
                            <i class="material-icons-two-tone text-primary me-2" style="font-size: 1.8rem;">import_contacts</i>
                            Kartu Stok Persediaan Barang
                        </h4>
                        <p class="text-muted small mb-0">
                            Pencatatan mutasi kuantitas fisik dan valuasi persediaan (HPP) pergerakan barang secara detail dan kronologis.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($currentProduct)
                            <a href="{{ route('stock-cards.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm shadow-sm" title="Cetak Kartu Stok">
                                <i class="feather icon-printer me-1"></i> Cetak Kartu Stok
                            </a>
                            <a href="{{ route('stock-cards.export-excel', request()->all()) }}" class="btn btn-outline-success btn-sm shadow-sm" title="Export Excel/CSV">
                                <i class="feather icon-download me-1"></i> Export Excel
                            </a>
                            <form action="{{ route('stock-cards.recalculate') }}" method="POST" class="d-inline" onsubmit="return confirm('Hitung ulang saldo kronologis untuk produk ini?')">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $currentProduct->id }}">
                                <button type="submit" class="btn btn-light-primary btn-sm shadow-sm" title="Sinkronkan & hitung ulang saldo berjalan">
                                    <i class="feather icon-refresh-cw me-1"></i> Hitung Ulang Saldo
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Filter Form -->
                <form action="{{ route('stock-cards.index') }}" method="GET" id="stock-card-filter-form" class="row g-3 align-items-end">
                    <!-- 1. Select Product -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-bold small text-dark mb-1">
                            <i class="feather icon-package me-1 text-primary"></i> Pilih Produk <span class="text-danger">*</span>
                        </label>
                        <select name="product_id" id="product_select" class="form-select select2-products" required>
                            @foreach($allProducts as $p)
                                <option value="{{ $p->id }}" {{ $currentProduct && $currentProduct->id === $p->id ? 'selected' : '' }}>
                                    {{ $p->item_name }} ({{ $p->sku ?: 'No SKU' }}) {{ $p->brand ? '• ' . $p->brand : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Select Rack / Location -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold small text-dark mb-1">
                            <i class="feather icon-layers me-1 text-primary"></i> Lokasi Rak
                        </label>
                        <select name="rack_id" class="form-select form-select-sm">
                            <option value="">-- Semua Rak (Global) --</option>
                            @foreach($allRacks as $r)
                                <option value="{{ $r->rak_kode }}" {{ $rackId === $r->rak_kode ? 'selected' : '' }}>
                                    {{ $r->rak_kode }} ({{ strtoupper($r->gudang ?: 'JS') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Mutation Type -->
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-bold small text-dark mb-1">
                            <i class="feather icon-filter me-1 text-primary"></i> Tipe Mutasi
                        </label>
                        <select name="mutation_type" class="form-select form-select-sm">
                            <option value="">Semua Mutasi</option>
                            <option value="initial_stock" {{ $mutationType === 'initial_stock' ? 'selected' : '' }}>Saldo Awal</option>
                            <option value="inbound" {{ $mutationType === 'inbound' ? 'selected' : '' }}>Barang Masuk (Inbound)</option>
                            <option value="outbound" {{ $mutationType === 'outbound' ? 'selected' : '' }}>Barang Keluar (Outbound)</option>
                            <option value="transfer" {{ $mutationType === 'transfer' ? 'selected' : '' }}>Pindah Rak (Transfer)</option>
                            <option value="adjustment" {{ $mutationType === 'adjustment' ? 'selected' : '' }}>Penyesuaian (Adjustment)</option>
                            <option value="return" {{ $mutationType === 'return' ? 'selected' : '' }}>Retur</option>
                        </select>
                    </div>

                    <!-- 4. Date Range -->
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-bold small text-dark mb-1">
                            <i class="feather icon-calendar me-1 text-primary"></i> Periode Dari
                        </label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-bold small text-dark mb-1">
                            <i class="feather icon-calendar me-1 text-primary"></i> Sampai
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
                            <button type="submit" class="btn btn-primary" title="Terapkan Filter">
                                <i class="feather icon-search"></i>
                            </button>
                            <a href="{{ route('stock-cards.index', ['product_id' => $currentProduct?->id]) }}" class="btn btn-light-secondary" title="Reset Filter Tanggal & Tipe">
                                <i class="feather icon-rotate-ccw"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($currentProduct)
            <!-- Product Information & Rack Allocation Banner -->
            <div class="card shadow-sm mb-4 border-0 bg-light-primary border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary fs-6">{{ $currentProduct->sku ?: 'No SKU' }}</span>
                                <h5 class="fw-bold mb-0 text-dark">{{ $currentProduct->item_name }}</h5>
                            </div>
                            <div class="text-muted small">
                                <span>Kategori: <b>{{ $currentProduct->category ?: '-' }}</b></span> • 
                                <span>Sub Kategori: <b>{{ $currentProduct->sub_category ?: '-' }}</b></span> • 
                                <span>Brand: <b>{{ $currentProduct->brand ?: '-' }}</b></span> • 
                                <span>Supplier: <b>{{ $currentProduct->supplier?->name ?: 'Umum' }}</b></span>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-2 mt-md-0">
                            <div class="small text-muted mb-1">Total Stok Fisik Saat Ini (Semua Rak):</div>
                            <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                <span class="badge bg-dark fs-6 px-3 py-2">{{ number_format($currentPhysicalStock) }} {{ $currentProduct->unit ?: 'pcs' }}</span>
                                @foreach($rackAllocations as $alloc)
                                    <span class="badge bg-white text-dark border shadow-sm">
                                        <i class="feather icon-inbox text-primary me-1"></i> {{ $alloc->rack_id ?: 'Unassigned' }}: <b>{{ $alloc->qty }}</b>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="row g-3 mb-4">
                <!-- 1. Saldo Awal -->
                <div class="col-xl-3 col-sm-6">
                    <div class="card kpi-card bg-white shadow-sm p-3 h-100 border-start border-4 border-secondary">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Saldo Awal Periode</span>
                            <span class="badge bg-light text-secondary rounded-pill px-2 py-1"><i class="feather icon-rewind"></i></span>
                        </div>
                        <div class="d-flex align-items-baseline gap-2">
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($saldoAwalQty) }}</h3>
                            <span class="text-muted small">{{ $currentProduct->unit ?: 'pcs' }}</span>
                        </div>
                        <div class="text-muted small mt-2">
                            Nilai: <b>Rp {{ number_format($saldoAwalValue, 0, ',', '.') }}</b>
                        </div>
                    </div>
                </div>

                <!-- 2. Total Masuk -->
                <div class="col-xl-3 col-sm-6">
                    <div class="card kpi-card bg-white shadow-sm p-3 h-100 border-start border-4 border-success">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Total Barang Masuk (+)</span>
                            <span class="badge bg-light-success text-success rounded-pill px-2 py-1"><i class="feather icon-arrow-down-left"></i></span>
                        </div>
                        <div class="d-flex align-items-baseline gap-2">
                            <h3 class="fw-bold text-success mb-0">+{{ number_format($totalMasukPeriode) }}</h3>
                            <span class="text-muted small">{{ $currentProduct->unit ?: 'pcs' }}</span>
                        </div>
                        <div class="text-muted small mt-2">
                            Nilai: <b>Rp {{ number_format($totalNilaiMasuk, 0, ',', '.') }}</b>
                        </div>
                    </div>
                </div>

                <!-- 3. Total Keluar -->
                <div class="col-xl-3 col-sm-6">
                    <div class="card kpi-card bg-white shadow-sm p-3 h-100 border-start border-4 border-danger">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Total Barang Keluar (-)</span>
                            <span class="badge bg-light-danger text-danger rounded-pill px-2 py-1"><i class="feather icon-arrow-up-right"></i></span>
                        </div>
                        <div class="d-flex align-items-baseline gap-2">
                            <h3 class="fw-bold text-danger mb-0">-{{ number_format($totalKeluarPeriode) }}</h3>
                            <span class="text-muted small">{{ $currentProduct->unit ?: 'pcs' }}</span>
                        </div>
                        <div class="text-muted small mt-2">
                            Nilai: <b>Rp {{ number_format($totalNilaiKeluar, 0, ',', '.') }}</b>
                        </div>
                    </div>
                </div>

                <!-- 4. Saldo Akhir -->
                <div class="col-xl-3 col-sm-6">
                    <div class="card kpi-card bg-white shadow-sm p-3 h-100 border-start border-4 border-primary">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Saldo Akhir Periode</span>
                            <span class="badge bg-light-primary text-primary rounded-pill px-2 py-1"><i class="feather icon-check-circle"></i></span>
                        </div>
                        <div class="d-flex align-items-baseline gap-2">
                            <h3 class="fw-bold text-primary mb-0">{{ number_format($saldoAkhirPeriode) }}</h3>
                            <span class="text-muted small">{{ $currentProduct->unit ?: 'pcs' }}</span>
                        </div>
                        <div class="text-muted small mt-2">
                            Total Valuasi: <b class="text-primary">Rp {{ number_format($totalValuasiSaldo, 0, ',', '.') }}</b>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Stock Card Table -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center">
                        <i class="feather icon-list text-primary me-2"></i> Rincian Kronologis Mutasi Stok
                    </h6>
                    <small class="text-muted">
                        Periode: <b>{{ $startDate->format('d/m/Y') }}</b> s/d <b>{{ $endDate->format('d/m/Y') }}</b>
                        @if($rackId) | Lokasi Rak: <span class="badge bg-info text-white">{{ $rackId }}</span> @endif
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-stock-card align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th style="width: 140px;">Tanggal & Waktu</th>
                                <th>No Referensi / Dokumen</th>
                                <th style="width: 130px;">Tipe Mutasi</th>
                                <th style="width: 110px;">Lokasi Rak</th>
                                <th class="text-end text-success" style="width: 100px;">Masuk (+)</th>
                                <th class="text-end text-danger" style="width: 100px;">Keluar (-)</th>
                                <th class="text-end fw-bold text-primary" style="width: 110px;">Saldo Akhir</th>
                                <th class="text-end" style="width: 130px;">HPP Satuan</th>
                                <th class="text-end" style="width: 150px;">Total Nilai Transaksi</th>
                                <th class="text-end" style="width: 160px;">Valuasi Persediaan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Saldo Awal Row if not offset page 2+ -->
                            @if($mutations->currentPage() === 1)
                                <tr class="table-light">
                                    <td class="text-center text-muted fw-bold">-</td>
                                    <td class="fw-semibold text-muted">{{ $startDate->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">SALDO AWAL</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-white">Saldo Awal</span>
                                    </td>
                                    <td class="text-muted">{{ $rackId ?: 'Semua Rak' }}</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end fw-bold text-dark fs-6">{{ number_format($saldoAwalQty) }}</td>
                                    <td class="text-end text-muted">Rp {{ number_format($unitCost, 0, ',', '.') }}</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end fw-semibold text-dark">Rp {{ number_format($saldoAwalValue, 0, ',', '.') }}</td>
                                    <td class="text-muted small">Saldo Awal sebelum periode {{ $startDate->format('d/m/Y') }}</td>
                                </tr>
                            @endif

                            @forelse($mutations as $idx => $m)
                                <tr>
                                    <td class="text-center text-muted">{{ $mutations->firstItem() + $idx }}</td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">{{ $m->transaction_date ? $m->transaction_date->format('d M Y') : '-' }}</div>
                                        <small class="text-muted">{{ $m->transaction_date ? $m->transaction_date->format('H:i') : '' }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $m->reference_number ?: '-' }}</div>
                                        <small class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $m->mutation_code }}</small>
                                    </td>
                                    <td>
                                        @php $badge = $m->type_badge; @endphp
                                        <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                    </td>
                                    <td>
                                        @if($m->rack_id)
                                            <span class="badge bg-light text-dark border">
                                                <i class="feather icon-inbox text-primary me-1"></i>{{ $m->rack_id }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Global</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($m->qty_in > 0)
                                            <span class="fw-bold text-success">+{{ number_format($m->qty_in) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($m->qty_out > 0)
                                            <span class="fw-bold text-danger">-{{ number_format($m->qty_out) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-primary fs-6">
                                        {{ number_format($m->calculated_balance ?? $m->balance) }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        Rp {{ number_format($m->unit_cost ?: $unitCost, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($m->total_cost, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        Rp {{ number_format($m->calculated_balance_value ?? $m->balance_value, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $m->notes }}">
                                            {{ $m->notes ?: '-' }}
                                        </div>
                                        @if($m->user)
                                            <small class="text-muted"><i class="feather icon-user me-1"></i>{{ $m->user->name }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="material-icons-two-tone text-secondary" style="font-size: 3rem;">inventory</i>
                                            <p class="mt-2 mb-1 fw-bold">Belum ada catatan mutasi transaksi pada periode ini.</p>
                                            <small>Saldo awal barang pada periode ini tetap tercatat dan aman.</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end fw-bold">TOTAL MUTASI PERIODE INI:</th>
                                <th class="text-end text-success fw-bold">+{{ number_format($totalMasukPeriode) }}</th>
                                <th class="text-end text-danger fw-bold">-{{ number_format($totalKeluarPeriode) }}</th>
                                <th class="text-end text-primary fw-bold fs-6">{{ number_format($saldoAkhirPeriode) }}</th>
                                <th></th>
                                <th class="text-end text-dark fw-bold">Rp {{ number_format($totalNilaiMasuk + $totalNilaiKeluar, 0, ',', '.') }}</th>
                                <th class="text-end text-primary fw-bold">Rp {{ number_format($totalValuasiSaldo, 0, ',', '.') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($mutations->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">
                                Menampilkan {{ $mutations->firstItem() }} s/d {{ $mutations->lastItem() }} dari total {{ $mutations->total() }} mutasi
                            </small>
                            <div>
                                {{ $mutations->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- Empty state if no product chosen -->
            <div class="card shadow-sm border-0 py-5 text-center">
                <div class="card-body">
                    <i class="material-icons-two-tone text-primary" style="font-size: 4rem;">inventory_2</i>
                    <h5 class="fw-bold mt-3 mb-1">Belum Ada Produk yang Dipilih</h5>
                    <p class="text-muted small">Silakan pilih produk pada dropdown di atas untuk melihat kartu stok dan pergerakan persediaan barang.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-products').select2({
            theme: 'bootstrap-5',
            placeholder: 'Cari & pilih produk...',
            allowClear: false,
            width: '100%'
        }).on('change', function() {
            $('#stock-card-filter-form').submit();
        });
    });
</script>
@endpush
