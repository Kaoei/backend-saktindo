@extends('layouts.dashboard', [
    'title' => 'Client Management',
    'pageTitle' => 'Client Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Client Management</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Clients</h5>
                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                    <i class="material-icons-two-tone text-white">person_add</i>
                    Add Client
                </a>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="table-responsive">
                    <table id="clients-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Documents</th>
                                <th>Created</th>
                                <th class="text-end" style="width: 110px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge bg-light-secondary font-monospace">{{ $client->id }}</span>
                                    </td>
                                    <td class="align-middle">{{ $client->name }}</td>
                                    <td class="align-middle">{{ $client->phone }}</td>
                                    <td class="align-middle">{{ $client->email }}</td>
                                    <td class="align-middle">
                                        @if ($client->ktp_path)
                                            <a href="{{ asset('storage/' . $client->ktp_path) }}" target="_blank" class="badge bg-light-primary" title="View KTP">
                                                KTP
                                            </a>
                                        @endif
                                        @if ($client->npwp_path)
                                            <a href="{{ asset('storage/' . $client->npwp_path) }}" target="_blank" class="badge bg-light-success" title="View NPWP">
                                                NPWP
                                            </a>
                                        @endif
                                        @if (!$client->ktp_path && !$client->npwp_path)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $client->created_at?->format('Y-m-d') }}</td>
                                    <td class="align-middle text-end">
                                        <a href="{{ route('clients.edit', $client) }}" class="text-success" title="Edit">
                                            <i class="icon feather icon-edit f-16 text-success"></i>
                                        </a>

                                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline" onsubmit="return confirm('Delete this client?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn p-0 border-0 bg-transparent" title="Delete">
                                                <i class="feather icon-trash-2 ml-3 f-16 text-danger"></i>
                                            </button>
                                        </form>
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
            $('#clients-table').DataTable({
                pageLength: 10,
                order: [[5, 'desc']],
                language: { emptyTable: 'Belum ada klien.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [6] }
                ]
            });
        });
    </script>
@endpush
