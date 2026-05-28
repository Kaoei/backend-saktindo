@extends('layouts.dashboard', [
    'title' => 'Activity Log',
    'pageTitle' => 'Activity Log',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Activity Log</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Activity Log</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="activity-logs-table" class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Subject</th>
                                <th>IP</th>
                                <th>Properties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $log->user?->name ?: 'System' }}</span>
                                        <div class="text-muted small">{{ $log->user?->email }}</div>
                                    </td>
                                    <td><span class="badge bg-light-primary">{{ $log->action }}</span></td>
                                    <td>{{ $log->module ?: '-' }}</td>
                                    <td>
                                        <span class="d-block">{{ class_basename($log->subject_type ?: '-') }}</span>
                                        <small class="text-muted font-monospace">{{ $log->subject_id ?: '-' }}</small>
                                    </td>
                                    <td>{{ $log->ip_address ?: '-' }}</td>
                                    <td><code>{{ $log->properties ? json_encode($log->properties) : '-' }}</code></td>
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
            $('#activity-logs-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: { emptyTable: 'Belum ada activity log.' }
            });
        });
    </script>
@endpush
