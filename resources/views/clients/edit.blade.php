@extends('layouts.dashboard', [
    'title' => 'Edit Client',
    'pageTitle' => 'Client Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('clients.index').'">Client Management</a></li><li class="breadcrumb-item">Edit Client</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Edit Client</h5>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
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

                <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control" value="{{ old('name', $client->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input name="phone" type="text" class="form-control" value="{{ old('phone', $client->phone) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $client->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address', $client->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">KTP</label>
                            @if ($client->ktp_path)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/' . $client->ktp_path) }}" target="_blank" class="badge bg-light-primary">
                                        <i class="feather icon-file"></i> View Current KTP
                                    </a>
                                </div>
                            @endif
                            <input name="ktp" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Max 2MB. Format: JPG, PNG, JPEG.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">NPWP</label>
                            @if ($client->npwp_path)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/' . $client->npwp_path) }}" target="_blank" class="badge bg-light-success">
                                        <i class="feather icon-file"></i> View Current NPWP
                                    </a>
                                </div>
                            @endif
                            <input name="npwp" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Max 2MB. Format: JPG, PNG, JPEG.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
