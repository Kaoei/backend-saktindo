@extends('layouts.dashboard', [
    'title' => 'Import CSV',
    'pageTitle' => 'Import Customer dari CSV',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('master-customer.index').'">Master Customer</a></li><li class="breadcrumb-item">Import CSV</li>',
])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Import Customer dari CSV</h5>

                    <a href="{{ route('master-customer.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('import_errors'))
                        <div class="alert alert-warning">
                            <h6>Import Errors:</h6>
                            <ul class="mb-0">
                                @foreach (session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Format CSV:</h6>

                        <p class="mb-1">File CSV harus memiliki format berikut dengan header:</p>

                        <code>nama_customer,nama_pic,nomor_hp,email,alamat,kota,npwp,tipe_customer,termin,limit_piutang</code>

                        <p class="mb-0 mt-2"><strong>Keterangan:</strong></p>

                        <ul class="mb-0">
                            <li><strong>nama_customer:</strong> Nama customer, wajib diisi</li>
                            <li><strong>nama_pic:</strong> Nama PIC customer</li>
                            <li><strong>nomor_hp:</strong> Nomor HP customer</li>
                            <li><strong>email:</strong> Email customer, boleh dikosongkan</li>
                            <li><strong>alamat:</strong> Alamat customer</li>
                            <li><strong>kota:</strong> Kota customer</li>
                            <li><strong>npwp:</strong> NPWP customer, boleh dikosongkan</li>
                            <li><strong>tipe_customer:</strong> Isi dengan perusahaan atau perorangan</li>
                            <li><strong>termin:</strong> Jumlah hari termin, contoh 30</li>
                            <li><strong>limit_piutang:</strong> Limit piutang dalam angka, contoh 5000000</li>
                        </ul>
                    </div>

                    <form action="{{ route('master-customer.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Upload CSV File <span class="text-danger">*</span></label>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                                    <small class="text-muted">Format: CSV atau TXT</small>

                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100 mb-3">
                                    <i class="fas fa-upload"></i> Upload & Import
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Download Template CSV</h6>
                            <p class="text-muted mb-2">Download template CSV untuk memudahkan import data customer</p>

                            <a href="{{ route('master-customer.tamplate') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
@endsection