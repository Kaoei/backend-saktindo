@extends('layouts.dashboard', [
    'title' => 'Edit Penawaran',
    'pageTitle' => 'Penawaran',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('proposals.index').'">Penawaran</a></li><li class="breadcrumb-item">Edit Penawaran</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Edit Penawaran {{ $proposal->id }}</h5>
                <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
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

                <form method="POST" action="{{ route('proposals.update', $proposal) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="" disabled>Pilih client...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ old('client_id', $proposal->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} &mdash; {{ $client->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Judul Penawaran <span class="text-danger">*</span></label>
                        <input name="title" type="text" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $proposal->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Layanan <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="5" required>{{ old('description', $proposal->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal Penawaran (Rp) <span class="text-danger">*</span></label>
                        <input name="amount" type="number" step="1000" min="0"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', $proposal->amount) }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3">{{ old('notes', $proposal->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Penawaran</button>
                        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
