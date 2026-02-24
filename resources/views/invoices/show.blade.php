@extends('layouts.dashboard', [
    'title' => 'Detail Invoice',
    'pageTitle' => 'Invoice',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('invoices.index').'">Invoice</a></li><li class="breadcrumb-item">Detail</li>',
])

@section('content')
<div class="row">
    {{-- Main Invoice Detail --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">{{ $invoice->id }}</h5>
                    <small class="text-muted">Dibuat: {{ $invoice->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="d-flex gap-2">
                    @if($invoice->status === \App\Models\Invoice::STATUS_PAID)
                        <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="btn btn-success btn-sm">
                            <i class="feather icon-printer me-1"></i>Cetak Receipt
                        </a>
                    @endif
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
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
                        <small class="text-muted d-block">No. Invoice</small>
                        <strong>{{ $invoice->id }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge {{ $invoice->status_badge }} fs-6">{{ $invoice->status_label }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nominal</small>
                        <strong class="text-primary fs-5">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tanggal Lunas</small>
                        <strong>{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : '-' }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Finance</small>
                        <strong>{{ $invoice->creator->name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tanggal Dibuat</small>
                        <strong>{{ $invoice->created_at->format('d/m/Y') }}</strong>
                    </div>
                </div>

                @if($invoice->notes)
                    <div class="mb-0">
                        <small class="text-muted d-block mb-1">Catatan</small>
                        <div class="p-3 bg-light rounded border">
                            {!! nl2br(e($invoice->notes)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Related Proposal --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="material-icons-two-tone me-1">description</i>Penawaran Terkait</h5>
                <a href="{{ route('proposals.show', $invoice->proposal) }}" class="btn btn-outline-primary btn-sm">Lihat Penawaran</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Judul</small>
                        <strong>{{ $invoice->proposal->title }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Sales</small>
                        <strong>{{ $invoice->proposal->creator->name }}</strong>
                    </div>
                    <div class="col-sm-12">
                        <small class="text-muted d-block">Deskripsi</small>
                        <p class="mb-0">{{ Str::limit($invoice->proposal->description, 200) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="col-lg-4">
        {{-- Client Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="material-icons-two-tone me-1">person</i>Informasi Client</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $invoice->proposal->client->name }}</strong></p>
                <p class="mb-1 text-muted"><i class="feather icon-mail me-1"></i>{{ $invoice->proposal->client->email }}</p>
                <p class="mb-1 text-muted"><i class="feather icon-phone me-1"></i>{{ $invoice->proposal->client->phone }}</p>
                <p class="mb-0 text-muted"><i class="feather icon-map-pin me-1"></i>{{ $invoice->proposal->client->address }}</p>
            </div>
        </div>

        {{-- Mark Paid Card --}}
        @if($invoice->status === \App\Models\Invoice::STATUS_PENDING)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="material-icons-two-tone me-1">payments</i>Konfirmasi Pembayaran</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Tandai invoice ini sebagai lunas setelah pembayaran diterima.</p>
                    <form method="POST" action="{{ route('invoices.markPaid', $invoice) }}">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Contoh: Transfer via BCA..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Tandai invoice ini sebagai PAID?')">
                            <i class="feather icon-check-circle me-2"></i>Tandai Lunas (Paid)
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card border-success">
                <div class="card-body text-center py-4">
                    <i class="feather icon-check-circle f-40 text-success d-block mb-2"></i>
                    <h6 class="text-success">Pembayaran Diterima</h6>
                    <p class="text-muted mb-3">{{ $invoice->paid_at->format('d/m/Y H:i') }}</p>
                    <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="btn btn-success">
                        <i class="feather icon-printer me-2"></i>Cetak Receipt
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
