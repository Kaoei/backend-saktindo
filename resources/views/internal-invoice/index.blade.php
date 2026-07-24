@extends('layouts.dashboard', [
    'title' => 'Invoice Internal',
    'pageTitle' => 'Invoice Internal',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Invoice Internal</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Daftar Invoice Internal (Koreksi Pembetulan Angka)</h5>
                <a href="{{ route('internal-invoices.create') }}" class="btn btn-primary btn-sm">
                    Buat Invoice Internal
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Invoice</th>
                                <th>Dari Toko</th>
                                <th>Ke Toko</th>
                                <th>Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th>Keterangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($internalInvoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">{{ $invoice->id }}</td>
                                    <td><span class="badge bg-light-info text-dark">{{ strtoupper($invoice->from_store) }}</span></td>
                                    <td><span class="badge bg-light-primary">{{ strtoupper($invoice->to_store) }}</span></td>
                                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                    <td>{{ $invoice->description ?: '-' }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('internal-invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data invoice internal ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $internalInvoices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
