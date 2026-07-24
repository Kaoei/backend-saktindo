@extends('layouts.dashboard', [
    'title' => 'Master Rekening Bank',
    'pageTitle' => 'Master Rekening Bank',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Rekening Bank</li>',
])

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Daftar Rekening Bank Toko</h5>
                <a href="{{ route('rekening-banks.create') }}" class="btn btn-primary btn-sm">
                    Tambah Rekening Bank
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Bank</th>
                                <th>Nama Pemilik Rekening</th>
                                <th>Nomor Rekening</th>
                                <th>Peruntukan Toko</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rekenings as $rekening)
                                <tr>
                                    <td>{{ $rekening->id }}</td>
                                    <td class="fw-semibold">{{ $rekening->bank_name }}</td>
                                    <td>{{ $rekening->account_name }}</td>
                                    <td><code>{{ $rekening->account_number }}</code></td>
                                    <td><span class="badge bg-light-info text-dark fw-bold">{{ strtoupper($rekening->toko) }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('rekening-banks.edit', $rekening) }}" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                        <form action="{{ route('rekening-banks.destroy', $rekening) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data rekening bank.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $rekenings->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
