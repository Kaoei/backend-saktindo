@extends('layouts.dashboard', [
    'title' => 'Add Client',
    'pageTitle' => 'Client Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('clients.index').'">Client Management</a></li><li class="breadcrumb-item">Add Client</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Add Client</h5>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input name="phone" type="text" class="form-control" value="{{ old('phone') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">KTP</label>
                            <input name="ktp" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Max 2MB</small> 
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">NPWP</label>
                            <input name="npwp" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Max 2MB</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
