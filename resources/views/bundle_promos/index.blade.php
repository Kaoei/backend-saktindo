@extends('layouts.dashboard', [
    'title' => 'Promo Bundling',
    'pageTitle' => 'Promo Bundling',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Promo Bundling</li>',
])

@section('content')

@php
    $activeCount = $activeBundles ?? 0;
    $totalCount = $totalBundles ?? 0;
@endphp

<div class="row mt-3">
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-bold">Total Paket Promo</div>
                    <h3 class="mb-0 fw-bold">{{ $totalCount }}</h3>
                </div>
                <div class="rounded-circle bg-light-primary p-3">
                    <i class="feather icon-package text-primary f-24"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-bold">Paket Aktif</div>
                    <h3 class="mb-0 fw-bold text-success">{{ $activeCount }}</h3>
                </div>
                <div class="rounded-circle bg-light-success p-3">
                    <i class="feather icon-check-circle text-success f-24"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-bold">Paket Non-Aktif</div>
                    <h3 class="mb-0 fw-bold text-secondary">{{ $totalCount - $activeCount }}</h3>
                </div>
                <div class="rounded-circle bg-light-secondary p-3">
                    <i class="feather icon-slash text-secondary f-24"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">Daftar Paket Promo Bundling</h5>
                    <small class="text-muted">Kombinasi produk dengan penawaran harga spesial untuk Pembelian & Penjualan.</small>
                </div>
                <div>
                    <a href="{{ route('bundle-promos.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="feather icon-plus-circle me-1"></i>
                        Buat Promo Bundling
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="feather icon-check me-1"></i> {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="feather icon-alert-octagon me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filter & Search -->
                <form method="GET" action="{{ route('bundle-promos.index') }}" class="row g-2 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama promo, kode, keterangan..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="feather icon-search me-1"></i> Filter
                        </button>
                    </div>
                    @if(!empty($search) || !empty($status))
                        <div class="col-md-2">
                            <a href="{{ route('bundle-promos.index') }}" class="btn btn-sm btn-light w-100">
                                Reset
                            </a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 140px;">KODE PROMO</th>
                                <th>NAMA PAKET & KETERANGAN</th>
                                <th>KOMPONEN PRODUK</th>
                                <th class="text-end">HARGA NORMAL</th>
                                <th class="text-end">HARGA BUNDLE</th>
                                <th class="text-center">HEMAT</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-end" style="width: 120px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bundlePromos as $bundle)
                                <tr>
                                    <td>
                                        <span class="badge bg-light-primary text-primary border font-monospace fw-bold">
                                            {{ $bundle->bundle_code }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6">{{ $bundle->name }}</div>
                                        @if($bundle->description)
                                            <small class="text-muted d-block">{{ Str::limit($bundle->description, 60) }}</small>
                                        @endif
                                        @if($bundle->start_date || $bundle->end_date)
                                            <small class="text-secondary d-block mt-1">
                                                <i class="feather icon-calendar me-1"></i>
                                                {{ $bundle->start_date ? $bundle->start_date->format('d M Y') : 'Mulai Sekarang' }}
                                                &mdash;
                                                {{ $bundle->end_date ? $bundle->end_date->format('d M Y') : 'Selamanya' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            @foreach($bundle->items as $item)
                                                <div class="text-nowrap">
                                                    &bull; <span class="fw-semibold text-dark">{{ $item->supplierProduct->item_name ?? '-' }}</span>
                                                    <span class="badge bg-light text-secondary border ms-1">{{ $item->qty }} pcs</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end text-muted">
                                        <del>Rp {{ number_format($bundle->original_price, 0, ',', '.') }}</del>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success fs-6">
                                            Rp {{ number_format($bundle->bundle_price, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($bundle->savings_amount > 0)
                                            <span class="badge bg-light-danger text-danger border border-danger-subtle">
                                                -{{ $bundle->savings_percentage }}% (Rp {{ number_format($bundle->savings_amount, 0, ',', '.') }})
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($bundle->status === 'active')
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Non-Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('bundle-promos.show', $bundle->id) }}" class="btn btn-sm btn-outline-info p-1 px-2 me-1" title="Lihat Detail">
                                            <i class="feather icon-eye"></i>
                                        </a>
                                        <a href="{{ route('bundle-promos.edit', $bundle->id) }}" class="btn btn-sm btn-outline-primary p-1 px-2 me-1" title="Edit">
                                            <i class="feather icon-edit"></i>
                                        </a>
                                        <form action="{{ route('bundle-promos.destroy', $bundle->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket promo ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Hapus">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="feather icon-package mb-2" style="font-size: 3rem;"></i>
                                        <div class="fw-semibold">Belum ada paket promo bundling.</div>
                                        <small>Klik tombol "Buat Promo Bundling" untuk membuat promo baru.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $bundlePromos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
