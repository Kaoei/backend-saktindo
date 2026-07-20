@extends('layouts.dashboard', [
    'title' => 'Import Stok Gudang',
    'pageTitle' => 'Import Stok Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('gudang-product.index').'">Stok Barang</a></li><li class="breadcrumb-item">Import</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error_list'))
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-2">Detail Error Import:</h6>
                <ul class="mb-0 ps-3">
                    @foreach(session('error_list') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <!-- Instructions and Template Download -->
            <div class="col-md-7 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold">Panduan Pengisian Data Import</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Untuk memastikan kelancaran import data stok barang, harap perhatikan panduan pengisian kolom Excel/CSV berikut:</p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Kolom</th>
                                        <th>Status</th>
                                        <th>Deskripsi & Format</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">ID</td>
                                        <td><span class="badge bg-secondary">Opsional</span></td>
                                        <td class="text-muted">ID stok gudang. Kosongkan jika ingin menambah data baru. Isi ID lama jika ingin mengupdate kuantitas/harga stok lama.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Serial Number</td>
                                        <td><span class="badge bg-danger">Wajib</span></td>
                                        <td class="text-muted">Kode serial number / SKU produk supplier. Produk dengan kode ini harus terdaftar di sistem.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Nama Barang</td>
                                        <td><span class="badge bg-secondary">Opsional / Info</span></td>
                                        <td class="text-muted">Nama produk. Bersifat informatif dan diabaikan saat proses import (sistem menggunakan Serial Number untuk pemetaan).</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Brand</td>
                                        <td><span class="badge bg-secondary">Opsional / Info</span></td>
                                        <td class="text-muted">Merek produk. Bersifat informatif dan diabaikan saat proses import.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Qty</td>
                                        <td><span class="badge bg-danger">Wajib</span></td>
                                        <td class="text-muted">Jumlah fisik stok. Harus berupa angka bulat positif (>= 0).</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Harga</td>
                                        <td><span class="badge bg-secondary">Opsional</span></td>
                                        <td class="text-muted">Harga beli per unit. Harus berupa angka positif. Default: 0.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kategori 1</td>
                                        <td><span class="badge bg-secondary">Opsional</span></td>
                                        <td class="text-muted">Kategori utama produk (misal: <code>Lamp</code>). Disimpan ke data produk.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kategori 2</td>
                                        <td><span class="badge bg-secondary">Opsional</span></td>
                                        <td class="text-muted">Sub-kategori produk (misal: <code>Bulb</code>, <code>LED</code>). Disimpan ke data produk.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Rak Kode</td>
                                        <td><span class="badge bg-danger">Wajib</span></td>
                                        <td class="text-muted">Kode rak penyimpanan gudang. Kode rak ini harus sudah terdaftar di master rak.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Lokasi</td>
                                        <td><span class="badge bg-secondary">Opsional / Info</span></td>
                                        <td class="text-muted">Keterangan lokasi rak. Bersifat informatif dan diabaikan saat proses import.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Status</td>
                                        <td><span class="badge bg-secondary">Opsional</span></td>
                                        <td class="text-muted">Status penyimpanan. Pilihan: <code>pending</code>, <code>stored</code>, <code>damaged</code>. Default: <code>stored</code>.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('gudang-product.download-template') }}" class="btn btn-outline-primary d-inline-flex align-items-center">
                                <span class="material-icons-two-tone text-primary me-2">download</span>
                                Download Template Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Upload Form -->
            <div class="col-md-5 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-semibold">Upload File Excel / CSV</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <form method="POST" action="{{ route('gudang-product.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-2">Pilih File</label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text mt-2 text-muted">Format file yang didukung: <strong>.xlsx, .xls, .csv</strong></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 d-inline-flex align-items-center justify-content-center">
                                <span class="material-icons-two-tone text-white me-2">publish</span>
                                Mulai Import Data
                            </button>
                        </form>
                        
                        <div class="mt-4 pt-3 border-top">
                            <a href="{{ route('gudang-product.index') }}" class="btn btn-light w-100 py-2">Kembali ke Stok Barang</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
