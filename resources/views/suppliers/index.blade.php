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
                    <h5 class="mb-1">Data Vendor</h5>
                    <small class="text-muted">Supplier, PIC, termin, barang, dan status vendor.</small>
                </div>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_business</i>
                        Tambah Supplier
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
                            <option value="latest" {{ ($sortBy ?? 'latest') === 'latest' ? 'selected' : '' }}>Terbaru (Default)</option>
                            <option value="oldest" {{ ($sortBy ?? '') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                            <option value="name_asc" {{ ($sortBy ?? '') === 'name_asc' ? 'selected' : '' }}>Nama Perusahaan (A - Z)</option>
                            <option value="name_desc" {{ ($sortBy ?? '') === 'name_desc' ? 'selected' : '' }}>Nama Perusahaan (Z - A)</option>
                            <option value="code_asc" {{ ($sortBy ?? '') === 'code_asc' ? 'selected' : '' }}>Kode Supplier (Asc)</option>
                            <option value="code_desc" {{ ($sortBy ?? '') === 'code_desc' ? 'selected' : '' }}>Kode Supplier (Desc)</option>
                            <option value="products_desc" {{ ($sortBy ?? '') === 'products_desc' ? 'selected' : '' }}>Barang Terbanyak</option>
                            <option value="products_asc" {{ ($sortBy ?? '') === 'products_asc' ? 'selected' : '' }}>Barang Tersedikit</option>
                            <option value="purchases_desc" {{ ($sortBy ?? '') === 'purchases_desc' ? 'selected' : '' }}>Pembelian Terbanyak</option>
                            <option value="termin_asc" {{ ($sortBy ?? '') === 'termin_asc' ? 'selected' : '' }}>Termin Terpendek</option>
                            <option value="termin_desc" {{ ($sortBy ?? '') === 'termin_desc' ? 'selected' : '' }}>Termin Terpanjang</option>
                            <option value="status_asc" {{ ($sortBy ?? '') === 'status_asc' ? 'selected' : '' }}>Status</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Filter Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipe Vendor</label>
                        <select id="filterType" class="form-select">
                            <option value="">Semua Tipe</option>
                            @foreach($suppliers->pluck('vendor_type')->filter()->unique()->sort() as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="resetFilter" class="btn btn-secondary w-100">
                            Reset Filter
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="suppliers-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Perusahaan</th>
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
                                    <td class="align-middle" data-order="{{ $supplier->id }}">
                                        <span class="badge bg-light-secondary font-monospace">{{ $supplier->id }}</span>
                                        <div class="small text-muted">{{ $supplier->vendor_code ?: '-' }}</div>
                                    </td>
                                    <td class="align-middle" data-order="{{ strtolower($supplier->name) }}">
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="fw-semibold">{{ $supplier->name }}</a>
                                        <div class="text-muted small">{{ $supplier->company_name ?: $supplier->vendor_type ?: '-' }}</div>
                                        @if($supplier->vendor_type)
                                            <span class="d-none">{{ $supplier->vendor_type }}</span>
                                        @endif
                                    </td>
                                    <td class="align-middle" data-order="{{ strtolower($supplier->pic_name ?? '') }}">
                                        <div>{{ $supplier->pic_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $supplier->pic_phone ?: $supplier->phone ?: '-' }}</small>
                                    </td>
                                    <td class="align-middle" data-order="{{ (int) $supplier->payment_due_days }}">{{ $supplier->payment_due_days }} hari</td>
                                    <td class="align-middle" data-order="{{ (int) $supplier->products_count }}"><span class="badge bg-light-primary">{{ $supplier->products_count }}</span></td>
                                    <td class="align-middle" data-order="{{ (int) $supplier->purchase_histories_count }}"><span class="badge bg-light-info">{{ $supplier->purchase_histories_count }}</span></td>
                                    <td class="align-middle" data-order="{{ $supplier->status }}">
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
            const sortMap = {
                'latest': [[0, 'desc']],
                'oldest': [[0, 'asc']],
                'name_asc': [[1, 'asc']],
                'name_desc': [[1, 'desc']],
                'code_asc': [[0, 'asc']],
                'code_desc': [[0, 'desc']],
                'products_desc': [[4, 'desc']],
                'products_asc': [[4, 'asc']],
                'purchases_desc': [[5, 'desc']],
                'termin_asc': [[3, 'asc']],
                'termin_desc': [[3, 'desc']],
                'status_asc': [[6, 'asc']]
            };

            const initialSort = '{{ $sortBy ?? "latest" }}';
            const initialOrder = sortMap[initialSort] || [[0, 'desc']];

            const table = $('#suppliers-table').DataTable({
                pageLength: 10,
                order: initialOrder,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ada supplier yang cocok",
                    emptyTable: "Belum ada supplier."
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

            // Filter Status
            $('#filterStatus').on('change', function () {
                table.column(6).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Filter Tipe Vendor
            $('#filterType').on('change', function () {
                table.column(1).search(this.value).draw();
            });

            // Reset Filter
            $('#resetFilter').on('click', function () {
                $('#sortBy').val('latest');
                $('#filterStatus').val('');
                $('#filterType').val('');

                table.search('').columns().search('').order([[0, 'desc']]).draw();

                const url = new URL(window.location.href);
                url.searchParams.delete('sort');
                window.history.replaceState({}, '', url.toString());
            });
        });
    </script>
@endpush
