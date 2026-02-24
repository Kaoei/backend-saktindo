@extends('layouts.dashboard', [
    'title' => 'Penawaran',
    'pageTitle' => 'Penawaran',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Penawaran</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Daftar Penawaran</h5>
                <a href="{{ route('proposals.create') }}" class="btn btn-primary">
                    <i class="material-icons-two-tone text-white">add_circle</i>
                    Buat Penawaran
                </a>
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
                    <table id="proposals-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Judul</th>
                                <th>Client</th>
                                <th>Sales</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Deadline Follow Up</th>
                                <th>Invoice</th>
                                <th class="text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proposals as $proposal)
                                <tr class="{{ $proposal->isOverdue() ? 'table-warning' : '' }}">
                                    <td class="align-middle"><span class="badge bg-light-secondary font-monospace">{{ $proposal->id }}</span></td>
                                    <td class="align-middle">
                                        <a href="{{ route('proposals.show', $proposal) }}" class="fw-semibold text-decoration-none">{{ $proposal->title }}</a>
                                        @if($proposal->email_sent_at)
                                            <br><small class="text-success"><i class="feather icon-mail f-12"></i> Email terkirim {{ $proposal->email_sent_at->format('d/m/Y') }}</small>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $proposal->client->name }}</td>
                                    <td class="align-middle">{{ $proposal->creator->name }}</td>
                                    <td class="align-middle">Rp {{ number_format($proposal->amount, 0, ',', '.') }}</td>
                                    <td class="align-middle">
                                        <span class="badge {{ $proposal->status_badge }}">{{ $proposal->status_label }}</span>
                                    </td>
                                    <td class="align-middle">
                                        {{ $proposal->follow_up_deadline->format('d/m/Y') }}
                                        @if($proposal->isOverdue())
                                            <br><small class="text-danger fw-semibold">Overdue</small>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        @if($proposal->invoice)
                                            <a href="{{ route('invoices.show', $proposal->invoice) }}" class="badge bg-light-primary text-decoration-none">
                                                {{ $proposal->invoice->id }}
                                            </a>
                                            <br><span class="badge {{ $proposal->invoice->status_badge }} mt-1">{{ $proposal->invoice->status_label }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-end">
                                        <a href="{{ route('proposals.show', $proposal) }}" class="text-primary" title="Lihat">
                                            <i class="feather icon-eye f-16 text-primary"></i>
                                        </a>
                                        <a href="{{ route('proposals.edit', $proposal) }}" class="text-success ms-1" title="Edit">
                                            <i class="feather icon-edit f-16 text-success"></i>
                                        </a>
                                        <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" class="d-inline" onsubmit="return confirm('Hapus penawaran ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn p-0 border-0 bg-transparent ms-1" title="Hapus">
                                                <i class="feather icon-trash-2 f-16 text-danger"></i>
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
            $('#proposals-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: { emptyTable: 'Belum ada penawaran.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [8] }
                ]
            });
        });
    </script>
@endpush
