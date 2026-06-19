@extends('layouts.dashboard', [
    'title' => 'Session Management',
    'pageTitle' => 'Session Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Session Management</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Active Sessions</h5>
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
                    <table class="table table-hover m-b-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Session</th>
                                <th>IP</th>
                                <th>Device</th>
                                <th>Last Activity</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $session->user_name ?: 'Guest' }}</span>
                                        <div class="text-muted small">{{ $session->user_email }}</div>
                                    </td>
                                    <td>
                                        <span class="font-monospace small">{{ $session->id }}</span>
                                        @if($session->id === $currentSessionId)
                                            <span class="badge bg-light-success ms-2">Current</span>
                                        @endif
                                    </td>
                                    <td>{{ $session->ip_address ?: '-' }}</td>
                                    <td class="text-truncate" style="max-width: 320px;">{{ $session->user_agent ?: '-' }}</td>
                                    <td>{{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->format('Y-m-d H:i:s') }}</td>
                                    <td class="text-end">
                                        @if($session->id !== $currentSessionId)
                                            <form method="POST" action="{{ route('sessions.destroy', $session->id) }}"
                                                  onsubmit="return confirm('Hapus session ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Belum ada session.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
