@extends('layouts.dashboard', [
    'title' => 'Barang Supplier',
    'pageTitle' => 'Master Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Barang Supplier</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Barang Supplier</h5>
                    <small class="text-muted">Mapping barang yang biasa dibeli dari masing-masing vendor.</small>
                </div>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.products.create') }}" class="btn btn-primary">
                        <i class="material-icons-two-tone text-white">add</i>
                        Tambah Barang
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
                    <table id="supplier-products-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>SKU / Part</th>
                                <th>Barang</th>
                                <th>Harga Terakhir</th>
                                <th>MOQ</th>
                                <th>Lead Time</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td class="align-middle">
                                        <a href="{{ route('suppliers.show', $product->supplier) }}" class="fw-semibold">{{ $product->supplier?->name }}</a>
                                    </td>
                                    <td class="align-middle"><span class="font-monospace">{{ $product->sku ?: '-' }}</span><div class="small text-muted">{{ $product->part_number ?: '-' }}</div></td>
                                    <td class="align-middle">{{ $product->item_name }}<div class="small text-muted">{{ collect([$product->brand, $product->category, $product->unit])->filter()->implode(' / ') }}</div></td>
                                    <td class="align-middle">Rp {{ number_format((float) $product->last_purchase_price, 0, ',', '.') }}</td>
                                    <td class="align-middle">{{ $product->minimum_order_qty }}</td>
                                    <td class="align-middle">{{ $product->lead_time_days }} hari</td>
                                    <td class="align-middle"><span class="badge {{ $product->status === 'active' ? 'bg-light-success' : 'bg-light-warning' }}">{{ ucfirst($product->status) }}</span></td>
                                    <td class="align-middle text-end">
                                        @if(auth()->user()?->hasPermission('suppliers.edit'))
                                            <a href="{{ route('suppliers.products.edit', $product) }}" class="text-success" title="Edit">
                                                <i class="feather icon-edit f-16 text-success"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('suppliers.delete'))
                                            <form method="POST" action="{{ route('suppliers.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Hapus barang supplier ini?')">
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
            $('#supplier-products-table').DataTable({
                pageLength: 10,
                language: { emptyTable: 'Belum ada barang supplier.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [7] }
                ]
            });
        });
    </script>
@endpush
