@extends('layouts.dashboard', [
    'title' => 'Form Kontak Supplier',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.contacts').'">Kontak Supplier</a></li><li class="breadcrumb-item">Form Kontak</li>',
])

@section('content')

@php
    $selectedSupplierId = old('supplier_id', $contact->supplier_id ?: request('supplier_id'));
@endphp

<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Kontak Supplier</h5>
                <a href="{{ route('suppliers.contacts') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
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

                    <div class="mb-3">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="" disabled {{ $selectedSupplierId ? '' : 'selected' }}>Pilih supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $selectedSupplierId === $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}{{ $supplier->vendor_code ? ' - '.$supplier->vendor_code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control" value="{{ old('name', $contact->name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan</label>
                            <input name="position" type="text" class="form-control" value="{{ old('position', $contact->position) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email', $contact->email) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telepon</label>
                            <input name="phone" type="text" class="form-control" value="{{ old('phone', $contact->phone) }}">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input name="is_primary" type="checkbox" value="1" id="is_primary" class="form-check-input" {{ old('is_primary', $contact->is_primary) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_primary">Kontak utama</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ route('suppliers.contacts') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
