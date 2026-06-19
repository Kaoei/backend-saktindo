@extends('layouts.dashboard', 
['title' => 'Master Customer',
 'pageTitle' => 'Master Customer', 
 'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Master Customer</li>'
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
                    <h5 class="mb-1">Daftar Customer</h5>
                    <small class="text-muted">Management data customer perusahaan & perorangan</small>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('master-customer.importPage') }}" class="btn btn-success btn-sm" >
                        <i class="fas fa-file-import"></i> Import CSV/Excel
                    </a>

                    <a href="{{ route('master-customer.export') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-file-export"></i> Export Data
                    </a>    

                    <a href="{{ route('master-customer.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Tambah Customer
                    </a>
                </div>

            </div>

            <div class="card-body">

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
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

                    <table id="customers-table" class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Customer</th>
                                <th>PIC</th>
                                <th>No HP</th>
                                <th>Email</th>
                                <th>Kota</th>
                                <th>Tipe</th>
                                <th>Termin</th>
                                <th>Limit Piutang</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($customers as $customer)

                                <tr>

                                    <td>{{ $customer->id }}</td>

                                    <td>{{ $customer->nama_customer }}</td>

                                    <td>{{ $customer->nama_pic }}</td>

                                    <td>{{ $customer->nomor_hp }}</td>

                                    <td>{{ $customer->email ?? '-' }}</td>

                                    <td>{{ $customer->kota }}</td>

                                    <td>
                                        @if($customer->tipe_customer == 'perusahaan')
                                            <span class="badge bg-light-primary">Perusahaan</span>
                                        @else
                                            <span class="badge bg-light-info">Perorangan</span>
                                        @endif
                                    </td>

                                    <td>{{ $customer->termin }} Hari</td>

                                    <td>Rp {{ number_format($customer->limit_piutang, 0, ',', '.') }}</td>

                                    <td class="text-end">

                                        <a href="{{ route('master-customer.edit', $customer->id) }}" class="text-success me-2">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>

                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-customer"
                                                data-customer-name="{{ $customer->nama_customer }}"
                                                data-customer-action="{{ route('master-customer.destroy', $customer->id) }}">

                                            <i class="feather icon-trash-2 f-18"></i>

                                        </button>

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

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="deleteModalLabel">
                    Hapus Customer
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <p class="mb-0">
                    Apakah Anda yakin ingin menghapus customer
                    <strong id="deleteCustomerName"></strong> ?
                </p>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>

                <form id="deleteForm" method="POST">

                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <i class="feather icon-trash-2 me-1"></i>
                        Hapus
                    </button>

                </form>

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

    $('#customers-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data customer.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [9]
            }
        ]
    });

    $(document).on('click', '.btn-delete-customer', function () {

        const btn = this;

        document.getElementById('deleteCustomerName').textContent =
            btn.dataset.customerName;

        document.getElementById('deleteForm').action =
            btn.dataset.customerAction;

        const modal = new bootstrap.Modal(
            document.getElementById('deleteModal')
        );

        modal.show();

    });

});

</script>

@endpush
