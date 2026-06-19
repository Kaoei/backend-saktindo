@extends('layouts.dashboard', [
    'title' => 'Master Supplier',
    'pageTitle' => 'Master Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Master Supplier</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

@php
    $activeCount = $suppliers->where('status', 'active')->count();
    $blockedCount = $suppliers->where('status', 'blocked')->count();
    $productCount = $suppliers->sum('products_count');
    $purchaseCount = $suppliers->sum('purchase_histories_count');
@endphp

<div class="row mt-3">
    <div class="col-md-3 col-sm-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Supplier</div>
            <h3 class="mb-0">{{ $suppliers->count() }}</h3>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Supplier Aktif</div>
            <h3 class="mb-0">{{ $activeCount }}</h3>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Barang Supplier</div>
            <h3 class="mb-0">{{ $productCount }}</h3>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Riwayat Pembelian</div>
            <h3 class="mb-0">{{ $purchaseCount }}</h3>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Data Vendor</h5>
                    <small class="text-muted">Supplier, PIC, termin, barang, dan status vendor.</small>
                </div>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                        <i class="material-icons-two-tone text-white">add_business</i>
                        Tambah Supplier
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="suppliers-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Supplier</th>
                                <th>PIC</th>
                                <th>Termin</th>
                                <th>Barang</th>
                                <th>Pembelian</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 130px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppliers as $supplier)
                                @php
                                    $statusClass = match ($supplier->status) {
                                        'blocked' => 'bg-light-danger',
                                        'inactive' => 'bg-light-warning',
                                        default => 'bg-light-success',
                                    };
                                @endphp
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge bg-light-secondary font-monospace">{{ $supplier->id }}</span>
                                        <div class="small text-muted">{{ $supplier->vendor_code ?: '-' }}</div>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="fw-semibold">{{ $supplier->name }}</a>
                                        <div class="text-muted small">{{ $supplier->company_name ?: $supplier->vendor_type ?: '-' }}</div>
                                    </td>
                                    <td class="align-middle">
                                        <div>{{ $supplier->pic_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $supplier->pic_phone ?: $supplier->phone ?: '-' }}</small>
                                    </td>
                                    <td class="align-middle">{{ $supplier->payment_due_days }} hari</td>
                                    <td class="align-middle"><span class="badge bg-light-primary">{{ $supplier->products_count }}</span></td>
                                    <td class="align-middle"><span class="badge bg-light-info">{{ $supplier->purchase_histories_count }}</span></td>
                                    <td class="align-middle">
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($supplier->status) }}</span>
                                        @if($supplier->status === 'blocked')
                                            <div class="small text-danger">Perlu review</div>
                                        @endif
                                    </td>
                                    <td class="align-middle text-end">
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-primary" title="Detail">
                                            <i class="feather icon-eye f-16 text-primary"></i>
                                        </a>
                                        @if(auth()->user()?->hasPermission('suppliers.edit'))
                                            <a href="{{ route('suppliers.edit', $supplier) }}" class="text-success ms-2" title="Edit">
                                                <i class="feather icon-edit f-16 text-success"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('suppliers.delete'))
                                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">
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

                @if($blockedCount > 0)
                    <div class="alert alert-warning mt-3 mb-0">Ada {{ $blockedCount }} supplier berstatus blocked.</div>
                @endif
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
            $('#suppliers-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: { emptyTable: 'Belum ada supplier.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [7] }
                ]
            });
        });
    </script>
@endpush
