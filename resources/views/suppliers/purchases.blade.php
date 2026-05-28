@extends('layouts.dashboard', [
    'title' => 'Riwayat Pembelian',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Riwayat Pembelian</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Riwayat Pembelian</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light-primary">{{ $purchaseHistories->count() }} transaksi</span>
                    @if(auth()->user()?->hasPermission('suppliers.create'))
                        <a href="{{ route('suppliers.purchases.create') }}" class="btn btn-primary btn-sm">Tambah Riwayat</a>
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
                    <table id="purchases-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Invoice</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseHistories as $history)
                                <tr>
                                    <td class="align-middle">{{ $history->purchase_date?->format('Y-m-d') }}</td>
                                    <td class="align-middle">
                                        <a href="{{ route('suppliers.show', $history->supplier) }}">{{ $history->supplier?->name }}</a>
                                        <div class="small text-muted font-monospace">{{ $history->supplier_id }}</div>
                                    </td>
                                    <td class="align-middle">{{ $history->invoice_number ?: '-' }}</td>
                                    <td class="align-middle fw-semibold">{{ $history->item_name }}</td>
                                    <td class="align-middle">{{ number_format((float) $history->quantity, 2) }}</td>
                                    <td class="align-middle">Rp {{ number_format((float) $history->unit_price, 0, ',', '.') }}</td>
                                    <td class="align-middle fw-semibold">Rp {{ number_format((float) $history->total_amount, 0, ',', '.') }}</td>
                                    <td class="align-middle"><span class="badge bg-light-secondary">{{ ucfirst($history->status) }}</span></td>
                                    <td class="align-middle text-end">
                                        @if(auth()->user()?->hasPermission('suppliers.edit'))
                                            <a href="{{ route('suppliers.purchases.edit', $history) }}" class="text-success" title="Edit">
                                                <i class="feather icon-edit f-16 text-success"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('suppliers.delete'))
                                            <form method="POST" action="{{ route('suppliers.purchases.destroy', $history) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus riwayat ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn p-0 border-0 bg-transparent ms-2" title="Delete">
                                                    <i class="feather icon-trash-2 f-16 text-danger"></i>
                                                </button>
                                            </form>
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
            $('#purchases-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: { emptyTable: 'Belum ada riwayat pembelian.' },
                columnDefs: [{ orderable: false, searchable: false, targets: [8] }]
            });
        });
    </script>
@endpush
