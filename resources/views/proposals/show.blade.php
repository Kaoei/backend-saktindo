@extends('layouts.dashboard', [
    'title' => 'Detail Penawaran',
    'pageTitle' => 'Penawaran',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('proposals.index').'">Penawaran</a></li><li class="breadcrumb-item">Detail</li>',
])

@section('content')
<div class="row">
    {{-- Left: Proposal Detail --}}
    <div class="col-lg-8">
        {{-- Proposal Info Card --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Penawaran {{ $proposal->id }}</h5>
                    <small class="text-muted">Dibuat: {{ $proposal->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-outline-primary btn-sm">
                        <i class="feather icon-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('proposals.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
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

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Judul Penawaran</small>
                        <strong>{{ $proposal->title }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge {{ $proposal->status_badge }} fs-6">{{ $proposal->status_label }}</span>
                        @if($proposal->isOverdue())
                            <span class="badge bg-danger ms-1">Overdue</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Sales</small>
                        <strong>{{ $proposal->creator->name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Deadline Follow Up</small>
                        <strong>{{ $proposal->follow_up_deadline->format('d/m/Y') }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nominal</small>
                        <strong class="text-primary fs-5">Rp {{ number_format($proposal->amount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Email Terkirim</small>
                        @if($proposal->email_sent_at)
                            <span class="text-success"><i class="feather icon-check-circle me-1"></i>{{ $proposal->email_sent_at->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-muted">Belum dikirim</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Deskripsi Layanan</small>
                    <div class="p-3 bg-light rounded border">
                        {!! nl2br(e($proposal->description)) !!}
                    </div>
                </div>

                @if($proposal->notes)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Catatan</small>
                        <div class="p-3 bg-light rounded border">
                            {!! nl2br(e($proposal->notes)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Invoice Info (if exists) --}}
        @if($proposal->invoice)
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="material-icons-two-tone me-1">receipt</i>Invoice</h5>
                    <a href="{{ route('invoices.show', $proposal->invoice) }}" class="btn btn-outline-primary btn-sm">Lihat Invoice</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <small class="text-muted d-block">No. Invoice</small>
                            <strong>{{ $proposal->invoice->id }}</strong>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Nominal</small>
                            <strong>Rp {{ number_format($proposal->invoice->amount, 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Status Pembayaran</small>
                            <span class="badge {{ $proposal->invoice->status_badge }}">{{ $proposal->invoice->status_label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Right: Actions Panel --}}
    <div class="col-lg-4">
        {{-- Client Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="material-icons-two-tone me-1">person</i>Informasi Client</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $proposal->client->name }}</strong></p>
                <p class="mb-1 text-muted"><i class="feather icon-mail me-1"></i>{{ $proposal->client->email }}</p>
                <p class="mb-1 text-muted"><i class="feather icon-phone me-1"></i>{{ $proposal->client->phone }}</p>
                <p class="mb-0 text-muted"><i class="feather icon-map-pin me-1"></i>{{ $proposal->client->address }}</p>
            </div>
        </div>

        {{-- Send Email Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="material-icons-two-tone me-1">email</i>Kirim Penawaran</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Kirim email penawaran ini ke <strong>{{ $proposal->client->email }}</strong>.
                </p>
                @if($proposal->email_sent_at)
                    <div class="alert alert-success py-2 mb-3">
                        <small><i class="feather icon-check me-1"></i>Terkirim pada {{ $proposal->email_sent_at->format('d/m/Y H:i') }}</small>
                    </div>
                @endif
                <form method="POST" action="{{ route('proposals.sendEmail', $proposal) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100"
                            onclick="return confirm('Kirim email penawaran ke {{ $proposal->client->email }}?')">
                        <i class="feather icon-send me-2"></i>Kirim Email ke Client
                    </button>
                </form>
            </div>
        </div>

        {{-- Update Status Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="material-icons-two-tone me-1">update</i>Update Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('proposals.updateStatus', $proposal) }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Status Penawaran</label>
                        <select name="status" class="form-select" required>
                            <option value="pending"    {{ $proposal->status === 'pending'    ? 'selected' : '' }}>Pending</option>
                            <option value="follow_up"  {{ $proposal->status === 'follow_up'  ? 'selected' : '' }}>Follow Up</option>
                            <option value="approved"   {{ $proposal->status === 'approved'   ? 'selected' : '' }}>Approved</option>
                            <option value="declined"   {{ $proposal->status === 'declined'   ? 'selected' : '' }}>Declined</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Alasan atau keterangan...">{{ $proposal->notes }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Update Status</button>
                </form>

                @if($proposal->status === 'approved' && ! $proposal->invoice)
                    <div class="alert alert-info mt-3 py-2 mb-0">
                        <small><i class="feather icon-info me-1"></i>Status Approved akan otomatis membuat task invoice untuk Finance.</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
