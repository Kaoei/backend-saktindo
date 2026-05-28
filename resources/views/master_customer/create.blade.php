@extends('layouts.dashboard', ['title' => 'Tambah Customer', 'pageTitle' => 'Master Customer', 'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('master-customer.index').'">Master Customer</a></li><li class="breadcrumb-item">Tambah Customer</li>'])

@section('content')

<div class="row justify-content-center">

    <div class="col-xl-10 col-lg-11">

        <div class="card">

            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-1">Tambah Customer Baru</h5>
                    <small class="text-muted">Tambahkan data customer perusahaan / perorangan</small>
                </div>

                <a href="{{ route('master-customer.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>

            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('master-customer.store') }}">

                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Customer <span class="text-danger">*</span></label>
                            <input type="text" name="nama_customer" class="form-control @error('nama_customer') is-invalid @enderror" value="{{ old('nama_customer') }}" placeholder="Masukkan nama customer" required>
                            @error('nama_customer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama PIC <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pic" class="form-control @error('nama_pic') is-invalid @enderror" value="{{ old('nama_pic') }}" placeholder="Masukkan nama PIC" required>
                            @error('nama_pic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_hp" class="form-control @error('nomor_hp') is-invalid @enderror" value="{{ old('nomor_hp') }}" placeholder="08xxxxxxxxxx" required>
                            @error('nomor_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="customer@email.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea name="alamat" rows="4" class="form-control @error('alamat') is-invalid @enderror" placeholder="Masukkan alamat customer" required>{{ old('alamat') }}</textarea>
                        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kota <span class="text-danger">*</span></label>
                            <input type="text" name="kota" class="form-control @error('kota') is-invalid @enderror" value="{{ old('kota') }}" placeholder="Jakarta" required>
                            @error('kota') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">NPWP</label>
                            <input type="text" name="npwp" class="form-control @error('npwp') is-invalid @enderror" value="{{ old('npwp') }}" placeholder="00.000.000.0-000.000">
                            @error('npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipe Customer <span class="text-danger">*</span></label>

                            <select name="tipe_customer" class="form-select @error('tipe_customer') is-invalid @enderror" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="perorangan" {{ old('tipe_customer') == 'perorangan' ? 'selected' : '' }}>Perorangan</option>
                                <option value="perusahaan" {{ old('tipe_customer') == 'perusahaan' ? 'selected' : '' }}>Perusahaan</option>
                            </select>

                            @error('tipe_customer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Termin Pembayaran</label>
                            <input type="number" name="termin" class="form-control @error('termin') is-invalid @enderror" value="{{ old('termin', 0) }}" placeholder="30">
                            @error('termin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Limit Piutang</label>
                            <input type="number" name="limit_piutang" class="form-control @error('limit_piutang') is-invalid @enderror" value="{{ old('limit_piutang', 0) }}" placeholder="10000000">
                            @error('limit_piutang') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">

                        <button type="submit" class="btn btn-primary">
                            Simpan Customer
                        </button>

                        <a href="{{ route('master-customer.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection