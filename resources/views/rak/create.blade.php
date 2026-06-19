@extends('layouts.dashboard',
[
    'title' => 'Tambah Rak',
    'pageTitle' => 'Rak',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('rak.index').'">Rak</a></li><li class="breadcrumb-item">Tambah Rak</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-1">Tambah Rak Baru</h5>
                    <small class="text-muted">Tambahkan data rak penyimpanan barang</small>
                </div>

                <a href="{{ route('rak.index') }}" class="btn btn-outline-secondary btn-sm">
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

                <form method="POST" action="{{ route('rak.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Kode Rak <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="rak_kode"
                                   class="form-control @error('rak_kode') is-invalid @enderror"
                                   value="{{ old('rak_kode') }}"
                                   placeholder="Contoh: A-01"
                                   required>
                            @error('rak_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                        <input type="text"
                               name="location"
                               class="form-control @error('location') is-invalid @enderror"
                               value="{{ old('location') }}"
                               placeholder="Contoh: Gudang Utama / Lantai 1"
                               required>
                        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            Simpan Rak
                        </button>

                        <a href="{{ route('rak.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection