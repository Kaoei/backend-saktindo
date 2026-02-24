@extends('layouts.dashboard', [
    'title' => 'Invoice',
    'pageTitle' => 'Invoice',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Invoice</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Invoice</h5>
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

                <div class="table-responsive">
                    <table id="invoices-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Client</th>
                                <th>Penawaran</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Tanggal Lunas</th>
                                <th>Dibuat</th>
                                <th class="text-end" style="width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="align-middle fw-semibold font-monospace">{{ $invoice->id }}</td>
                                    <td class="align-middle">{{ $invoice->proposal->client->name }}</td>
                                    <td class="align-middle">
                                        <a href="{{ route('proposals.show', $invoice->proposal) }}" class="text-decoration-none">
                                            {{ $invoice->proposal->title }}
                                        </a>
                                    </td>
                                    <td class="align-middle">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                    <td class="align-middle">
                                        <span class="badge {{ $invoice->status_badge }}">{{ $invoice->status_label }}</span>
                                    </td>
                                    <td class="align-middle">
                                        {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="align-middle">{{ $invoice->created_at->format('d/m/Y') }}</td>
                                    <td class="align-middle text-end">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-primary" title="Lihat">
                                            <i class="feather icon-eye f-16 text-primary"></i>
                                        </a>
                                        @if($invoice->status === \App\Models\Invoice::STATUS_PAID)
                                            <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="text-success ms-1" title="Cetak Receipt">
                                                <i class="feather icon-printer f-16 text-success"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#invoices-table').DataTable({
                pageLength: 10,
                order: [[6, 'desc']],
                language: { emptyTable: 'Belum ada invoice.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [7] }
                ]
            });
        });
    </script>
@endpush
