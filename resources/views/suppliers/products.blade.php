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
                    <h5 class="mb-1">Barang Supplier</h5>
                    <small class="text-muted">Mapping barang yang biasa dibeli dari masing-masing vendor.</small>
                </div>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.products.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add</i>
                        Tambah Barang
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Urutkan Berdasarkan</label>
                        <select id="sortBy" class="form-select">
                            <option value="item_asc" {{ ($sortBy ?? 'item_asc') === 'item_asc' ? 'selected' : '' }}>Nama Barang (A - Z)</option>
                            <option value="item_desc" {{ ($sortBy ?? '') === 'item_desc' ? 'selected' : '' }}>Nama Barang (Z - A)</option>
                            <option value="supplier_asc" {{ ($sortBy ?? '') === 'supplier_asc' ? 'selected' : '' }}>Nama Supplier (A - Z)</option>
                            <option value="supplier_desc" {{ ($sortBy ?? '') === 'supplier_desc' ? 'selected' : '' }}>Nama Supplier (Z - A)</option>
                            <option value="price_asc" {{ ($sortBy ?? '') === 'price_asc' ? 'selected' : '' }}>Harga Terendah</option>
                            <option value="price_desc" {{ ($sortBy ?? '') === 'price_desc' ? 'selected' : '' }}>Harga Tertinggi</option>
                            <option value="sku_asc" {{ ($sortBy ?? '') === 'sku_asc' ? 'selected' : '' }}>SKU (A - Z)</option>
                            <option value="sku_desc" {{ ($sortBy ?? '') === 'sku_desc' ? 'selected' : '' }}>SKU (Z - A)</option>
                            <option value="moq_asc" {{ ($sortBy ?? '') === 'moq_asc' ? 'selected' : '' }}>MOQ Terendah</option>
                            <option value="moq_desc" {{ ($sortBy ?? '') === 'moq_desc' ? 'selected' : '' }}>MOQ Tertinggi</option>
                            <option value="lead_time_asc" {{ ($sortBy ?? '') === 'lead_time_asc' ? 'selected' : '' }}>Lead Time Tercepat</option>
                            <option value="lead_time_desc" {{ ($sortBy ?? '') === 'lead_time_desc' ? 'selected' : '' }}>Lead Time Terlama</option>
                            <option value="status_asc" {{ ($sortBy ?? '') === 'status_asc' ? 'selected' : '' }}>Status</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Filter Kategori</label>
                        <select id="filterCategory" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach($products->pluck('category')->filter()->unique()->sort() as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Filter Supplier</label>
                        <select id="filterSupplier" class="form-select">
                            <option value="">Semua Supplier</option>
                            @foreach($products->pluck('supplier.name')->filter()->unique()->sort() as $sup)
                                <option value="{{ $sup }}">{{ $sup }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Filter Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="resetFilter" class="btn btn-secondary w-100">
                            Reset Filter
                        </button>
                    </div>
                </div>

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
                                    <td class="align-middle" data-order="{{ strtolower($product->supplier?->name ?? '') }}">
                                        <a href="{{ route('suppliers.show', $product->supplier) }}" class="fw-semibold">{{ $product->supplier?->name }}</a>
                                        @if($product->supplier?->name)
                                            <span class="d-none">{{ $product->supplier->name }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle" data-order="{{ strtolower($product->sku ?? '') }}">
                                        <span class="font-monospace">{{ $product->sku ?: '-' }}</span>
                                        <div class="small text-muted">{{ $product->part_number ?: '-' }}</div>
                                    </td>
                                    <td class="align-middle" data-order="{{ strtolower($product->item_name ?? '') }}">
                                        {{ $product->item_name }}
                                        <div class="small text-muted">{{ collect([$product->brand, $product->category, $product->unit])->filter()->implode(' / ') }}</div>
                                        @if($product->category)
                                            <span class="d-none">{{ $product->category }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle" data-order="{{ (float) $product->last_purchase_price }}">Rp {{ number_format((float) $product->last_purchase_price, 0, ',', '.') }}</td>
                                    <td class="align-middle" data-order="{{ (int) $product->minimum_order_qty }}">{{ $product->minimum_order_qty }}</td>
                                    <td class="align-middle" data-order="{{ (int) $product->lead_time_days }}">{{ $product->lead_time_days }} hari</td>
                                    <td class="align-middle" data-order="{{ $product->status }}"><span class="badge {{ $product->status === 'active' ? 'bg-light-success' : 'bg-light-warning' }}">{{ ucfirst($product->status) }}</span></td>
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
            const sortMap = {
                'item_asc': [[2, 'asc']],
                'item_desc': [[2, 'desc']],
                'supplier_asc': [[0, 'asc']],
                'supplier_desc': [[0, 'desc']],
                'price_asc': [[3, 'asc']],
                'price_desc': [[3, 'desc']],
                'sku_asc': [[1, 'asc']],
                'sku_desc': [[1, 'desc']],
                'moq_asc': [[4, 'asc']],
                'moq_desc': [[4, 'desc']],
                'lead_time_asc': [[5, 'asc']],
                'lead_time_desc': [[5, 'desc']],
                'status_asc': [[6, 'asc']],
                'latest': [[2, 'asc']]
            };

            const initialSort = '{{ $sortBy ?? "item_asc" }}';
            const initialOrder = sortMap[initialSort] || [[2, 'asc']];

            const table = $('#supplier-products-table').DataTable({
                pageLength: 10,
                order: initialOrder,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ada barang yang cocok",
                    emptyTable: "Belum ada barang supplier."
                },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [7] }
                ]
            });

            // Urutkan Berdasarkan
            $('#sortBy').on('change', function () {
                const val = $(this).val();
                if (sortMap[val]) {
                    table.order(sortMap[val]).draw();
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', val);
                    window.history.replaceState({}, '', url.toString());
                }
            });

            // Filter Kategori
            $('#filterCategory').on('change', function () {
                table.column(2).search(this.value ? this.value : '').draw();
            });

            // Filter Supplier
            $('#filterSupplier').on('change', function () {
                table.column(0).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Filter Status
            $('#filterStatus').on('change', function () {
                table.column(6).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Reset Filter
            $('#resetFilter').on('click', function () {
                $('#sortBy').val('item_asc');
                $('#filterCategory').val('');
                $('#filterSupplier').val('');
                $('#filterStatus').val('');

                table.search('').columns().search('').order([[2, 'asc']]).draw();

                const url = new URL(window.location.href);
                url.searchParams.delete('sort');
                window.history.replaceState({}, '', url.toString());
            });
        });
    </script>
@endpush
