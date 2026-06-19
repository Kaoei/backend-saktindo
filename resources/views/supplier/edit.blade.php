@extends('layouts.dashboard',
[
    'title' => 'Edit Supplier',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier.index').'">Supplier</a></li><li class="breadcrumb-item">Edit Supplier</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="card">

            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Edit Supplier: {{ $supplier->name }}</h5>
                    <small class="text-muted">
                        Kode Supplier:
                        <span class="font-monospace">{{ $supplier->id_supplier }}</span>
                    </small>
                </div>

                <a href="{{ route('supplier.index') }}" class="btn btn-outline-secondary btn-sm">
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

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('supplier.update', $supplier->id_supplier) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID Supplier</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $supplier->id_supplier }}"
                                   disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $supplier->name) }}"
                                   placeholder="Masukkan nama supplier"
                                   required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat"
                                  rows="4"
                                  class="form-control @error('alamat') is-invalid @enderror"
                                  placeholder="Masukkan alamat supplier">{{ old('alamat', $supplier->alamat) }}</textarea>
                        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No Telepon</label>
                            <input type="text"
                                   name="no_tlp"
                                   class="form-control @error('no_tlp') is-invalid @enderror"
                                   value="{{ old('no_tlp', $supplier->no_tlp) }}"
                                   placeholder="08xxxxxxxxxx">
                            @error('no_tlp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $supplier->email) }}"
                                   placeholder="supplier@email.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            Update Supplier
                        </button>

                        <a href="{{ route('supplier.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

@endsection