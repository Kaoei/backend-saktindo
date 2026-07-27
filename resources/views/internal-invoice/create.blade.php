@extends('layouts.dashboard', [
    'title' => 'Buat Invoice Internal',
    'pageTitle' => 'Buat Invoice Internal',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('internal-invoices.index').'">Invoice Internal</a></li><li class="breadcrumb-item">Buat</li>',
])

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('internal-invoices.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Form Pembuatan Invoice Internal</h5>
                <p class="text-muted small mb-0">Invoice ini digunakan hanya untuk koreksi pembetulan angka antar toko dan **tidak mengurangi stok gudang**.</p>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 mb-3">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dari Toko</label>
                        <select name="from_store" class="form-select" required>
                            <option value="js">JS</option>
                            <option value="sjb">SJB</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ke Toko</label>
                        <select name="to_store" class="form-select" required>
                            <option value="sjb">SJB</option>
                            <option value="js">JS</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nominal Pembetulan (Rp)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Masukkan jumlah nominal..." required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan / Alasan Pembetulan</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Contoh: Koreksi selisih pembetulan angka penjualan bulan Juni..." required></textarea>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <a href="{{ route('internal-invoices.index') }}" class="btn btn-light me-2">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Invoice Internal</button>
            </div>
        </form>
    </div>
</div>
@endsection
