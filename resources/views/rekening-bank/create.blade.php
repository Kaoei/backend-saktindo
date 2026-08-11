@extends('layouts.dashboard', [
    'title' => 'Tambah Rekening Bank',
    'pageTitle' => 'Tambah Rekening Bank',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('rekening-banks.index').'">Rekening Bank</a></li><li class="breadcrumb-item">Tambah</li>',
])

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('rekening-banks.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold">Tambah Rekening Bank Toko</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Bank</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="Contoh: Bank BCA, Bank Mandiri..." required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Peruntukan Toko</label>
                        <select name="toko" class="form-select" required>
                            <option value="js">JS</option>
                            <option value="sjb">SJB</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Pemilik Rekening</label>
                        <input type="text" name="account_name" class="form-control" placeholder="Masukkan nama pemilik..." required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="account_number" class="form-control" placeholder="Masukkan nomor rekening..." required>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <a href="{{ route('rekening-banks.index') }}" class="btn btn-light me-2">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Rekening Bank</button>
            </div>
        </form>
    </div>
</div>
@endsection
