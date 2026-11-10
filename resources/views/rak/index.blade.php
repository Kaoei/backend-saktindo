@extends('layouts.dashboard',
[
    'title' => 'Rak',
    'pageTitle' => 'Rak',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Rak</li>'
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
                    <h5 class="mb-1">Daftar Rak</h5>
                    <small class="text-muted">Data rak penyimpanan barang</small>
                </div>

                <div>
                    <a href="{{ route('rak.create') }}" class="btn btn-primary btn-sm">
                        <i class="material-icons-two-tone text-white">add_circle</i>
                        Tambah Rak
                    </a>
                </div>

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

                    <table id="rak-table" class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th>Kode Rak</th>
                                <th>Lokasi</th>
                                <th>Gudang</th>
                                <th>Produk Tersimpan</th>
                                <th class="text-end" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($raks as $rak)
                                @php
                                    $prodCount = $rak->gudang_products_count ?? $rak->gudangProducts->count();
                                    $totalQty = $rak->gudangProducts->sum('qty');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)" 
                                           class="fw-bold text-primary text-decoration-none btn-quick-view-rack" 
                                           data-rack="{{ $rak->rak_kode }}" 
                                           data-gudang="{{ strtoupper($rak->gudang ?? 'JS') }}"
                                           title="Klik untuk langsung melihat produk di rak {{ $rak->rak_kode }}">
                                            <i class="feather icon-layers me-1"></i> {{ $rak->rak_kode }}
                                        </a>
                                    </td>
                                    <td>{{ $rak->location ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-light-info text-info">{{ strtoupper($rak->gudang ?? 'JS') }}</span>
                                    </td>
                                    <td>
                                        @if($prodCount > 0)
                                            <a href="javascript:void(0)" 
                                               class="badge bg-light-success text-success text-decoration-none border border-success-subtle py-1 px-2 btn-quick-view-rack" 
                                               data-rack="{{ $rak->rak_kode }}" 
                                               data-gudang="{{ strtoupper($rak->gudang ?? 'JS') }}"
                                               title="Klik untuk langsung melihat produk di rak ini">
                                                <i class="feather icon-package me-1"></i> {{ $prodCount }} Produk ({{ number_format($totalQty) }} pcs)
                                            </a>
                                        @else
                                            <span class="badge bg-light text-muted border py-1 px-2">Kosong (0 produk)</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('stock-cards.index', ['gudang_type' => $rak->gudang, 'rack_id' => $rak->rak_kode]) }}"
                                           class="text-primary me-2" title="Lihat Kartu Stok Rak Ini">
                                            <i class="feather icon-book-open f-18"></i>
                                        </a>

                                        <a href="{{ route('rak.edit', $rak->rak_kode) }}"
                                           class="text-success me-2" title="Edit Rak">
                                            <i class="feather icon-edit f-18"></i>
                                        </a>

                                        <button type="button"
                                                class="btn p-0 border-0 bg-transparent text-danger btn-delete-rak"
                                                data-rak-name="{{ $rak->rak_kode }}"
                                                data-rak-action="{{ route('rak.destroy', $rak->rak_kode) }}"
                                                title="Hapus Rak">
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    Hapus Rak
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Apakah Anda yakin ingin menghapus rak
                    <strong id="deleteRakName"></strong> ?
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

{{-- Modal Quick View Produk Tersimpan di Rak --}}
<div class="modal fade" id="rackProductsModal" tabindex="-1" aria-labelledby="rackProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white fw-bold mb-0" id="rackProductsModalLabel">
                    <i class="feather icon-layers me-2"></i> Daftar Produk di Rak <span id="quickViewRackTitle">-</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="quickViewLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Memuat daftar produk di rak...</p>
                </div>

                <div id="quickViewEmpty" class="text-center py-4 d-none">
                    <div class="mb-2"><i class="feather icon-inbox text-muted fs-1"></i></div>
                    <h6 class="text-muted fw-bold">Rak Ini Kosong</h6>
                    <p class="text-muted small mb-0">Belum ada produk atau barang yang disimpan di rak ini.</p>
                </div>

                <div id="quickViewContent" class="table-responsive d-none">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Nama Produk</th>
                                <th>SKU</th>
                                <th>Brand</th>
                                <th class="text-center">Stok di Rak</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="quickViewTableBody">
                            {{-- Injected dynamically --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-between">
                <div>
                    <span id="quickViewTotalCount" class="badge bg-secondary">0 Produk</span>
                </div>
                <div class="d-flex gap-2">
                    <a id="quickViewStockCardLink" href="#" class="btn btn-sm btn-outline-primary shadow-sm" target="_blank">
                        <i class="feather icon-book-open me-1"></i> Buka Kartu Stok
                    </a>
                    <a id="quickViewFullLink" href="#" class="btn btn-sm btn-primary shadow-sm">
                        <i class="feather icon-external-link me-1"></i> Kelola di Stok Gudang
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
    $('#rak-table').DataTable({
        pageLength: 25,
        language: {
            emptyTable: 'Belum ada data rak.'
        },
        columnDefs: [
            {
                orderable: false,
                targets: [2]
            }
        ]
    });

    $(document).on('click', '.btn-delete-rak', function () {
        const btn = this;

        document.getElementById('deleteRakName').textContent =
            btn.dataset.rakName;

        document.getElementById('deleteForm').action =
            btn.dataset.rakAction;

        const modal = new bootstrap.Modal(
            document.getElementById('deleteModal')
        );

        modal.show();
    });

    // Quick view products stored in a rack on click
    $(document).on('click', '.btn-quick-view-rack', function (e) {
        e.preventDefault();
        const rackKode = $(this).data('rack');
        const gudang = $(this).data('gudang') || 'JS';

        $('#quickViewRackTitle').text(rackKode + ' (Gudang ' + gudang + ')');
        $('#quickViewLoading').removeClass('d-none');
        $('#quickViewEmpty').addClass('d-none');
        $('#quickViewContent').addClass('d-none');
        $('#quickViewTableBody').empty();

        const stockCardUrl = "{{ route('stock-cards.index') }}?gudang_type=" + encodeURIComponent(gudang) + "&rack_id=" + encodeURIComponent(rackKode);
        const gudangProductUrl = "{{ route('gudang-product.index') }}?rack=" + encodeURIComponent(rackKode);

        $('#quickViewStockCardLink').attr('href', stockCardUrl);
        $('#quickViewFullLink').attr('href', gudangProductUrl);

        const modalEl = document.getElementById('rackProductsModal');
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        modalInstance.show();

        $.ajax({
            url: "{{ route('stock-cards.products-by-rack') }}",
            type: 'GET',
            data: { rack_id: rackKode },
            dataType: 'json',
            success: function (response) {
                $('#quickViewLoading').addClass('d-none');
                const products = response.products || [];
                $('#quickViewTotalCount').text(products.length + ' Produk');

                if (products.length === 0) {
                    $('#quickViewEmpty').removeClass('d-none');
                    return;
                }

                let rowsHtml = '';
                $.each(products, function (i, item) {
                    const cardUrl = "{{ route('stock-cards.index') }}?rack_id=" + encodeURIComponent(rackKode) + "&product_id=" + item.id;
                    rowsHtml += `
                        <tr>
                            <td class="text-muted small">${i + 1}</td>
                            <td><span class="fw-semibold text-dark">${item.item_name}</span></td>
                            <td><code>${item.sku || '-'}</code></td>
                            <td><span class="badge bg-light text-muted border">${item.brand || '-'}</span></td>
                            <td class="text-center"><span class="badge bg-light-success text-success fw-bold px-2 py-1">${item.qty != null ? item.qty : 0} pcs</span></td>
                            <td class="text-end">
                                <a href="${cardUrl}" class="btn btn-xs btn-outline-primary" title="Lihat Kartu Stok Produk Ini">
                                    <i class="feather icon-book-open"></i> Kartu Stok
                                </a>
                            </td>
                        </tr>
                    `;
                });

                $('#quickViewTableBody').html(rowsHtml);
                $('#quickViewContent').removeClass('d-none');
            },
            error: function () {
                $('#quickViewLoading').addClass('d-none');
                $('#quickViewTableBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Gagal memuat produk rak. Silakan coba kembali.</td></tr>');
                $('#quickViewContent').removeClass('d-none');
            }
        });
    });
});
</script>

@endpush