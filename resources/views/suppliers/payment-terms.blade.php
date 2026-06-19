@extends('layouts.dashboard', [
    'title' => 'Termin Pembayaran',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Termin Pembayaran</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Termin Pembayaran</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light-primary">{{ $paymentTerms->count() }} termin</span>
                    @if(auth()->user()?->hasPermission('suppliers.create'))
                        <a href="{{ route('suppliers.payment-terms.create') }}" class="btn btn-primary btn-sm">Tambah Termin</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <div class="table-responsive">
                    <table id="payment-terms-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Termin</th>
                                <th>Supplier</th>
                                <th>Jatuh Tempo</th>
                                <th>DP</th>
                                <th>Denda</th>
                                <th>Default</th>
                                <th>Catatan</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentTerms as $term)
                                <tr>
                                    <td class="align-middle fw-semibold">{{ $term->name }}</td>
                                    <td class="align-middle">
                                        <a href="{{ route('suppliers.show', $term->supplier) }}">{{ $term->supplier?->name }}</a>
                                        <div class="small text-muted font-monospace">{{ $term->supplier_id }}</div>
                                    </td>
                                    <td class="align-middle">{{ $term->due_days }} hari</td>
                                    <td class="align-middle">{{ number_format((float) $term->down_payment_percent, 2) }}%</td>
                                    <td class="align-middle">{{ number_format((float) $term->late_fee_percent, 2) }}%</td>
                                    <td class="align-middle">
                                        @if($term->is_default)
                                            <span class="badge bg-light-success">Ya</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $term->notes ?: '-' }}</td>
                                    <td class="align-middle text-end">
                                        @if(auth()->user()?->hasPermission('suppliers.edit'))
                                            <a href="{{ route('suppliers.payment-terms.edit', $term) }}" class="text-success" title="Edit">
                                                <i class="feather icon-edit f-16 text-success"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('suppliers.delete'))
                                            <form method="POST" action="{{ route('suppliers.payment-terms.destroy', $term) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus termin ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn p-0 border-0 bg-transparent ms-2" title="Delete">
                                                    <i class="feather icon-trash-2 f-16 text-danger"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
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
            $('#payment-terms-table').DataTable({
                pageLength: 10,
                language: { emptyTable: 'Belum ada termin pembayaran.' },
                columnDefs: [{ orderable: false, searchable: false, targets: [7] }]
            });
        });
    </script>
@endpush
