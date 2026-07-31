@extends('layouts.dashboard', [
    'title' => 'Master Kategori',
    'pageTitle' => 'Master Kategori',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Master Kategori</li>'
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
                    <h5 class="mb-1">Daftar Kategori</h5>
                    <small class="text-muted">Kelola kategori utama produk</small>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                        <i class="feather icon-plus-circle me-1"></i> Tambah Kategori
                    </button>
                </div>
            </div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="categories-table" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $index => $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $category->name }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-success me-2 btn-edit" 
                                                data-id="{{ $category->id }}" 
                                                data-name="{{ $category->name }}"
                                                data-action="{{ route('categories.update', $category->id) }}">
                                            <i class="feather icon-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" 
                                                data-name="{{ $category->name }}"
                                                data-action="{{ route('categories.destroy', $category->id) }}">
                                            <i class="feather icon-trash-2"></i> Hapus
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title text-white d-flex align-items-center font-weight-bold">
                    <i class="feather icon-plus-circle me-2 font-size-lg"></i> Tambah Kategori Baru
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; outline: none; opacity: 0.9; cursor: pointer;" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="feather icon-x" style="font-size: 16px;"></i>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-dark mb-2">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg rounded-lg border" placeholder="Contoh: Lampu, Kabel, Peralatan Listrik" required style="font-size: 0.95rem;">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                        <i class="feather icon-check me-1"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-success text-white py-3 px-4">
                <h5 class="modal-title text-white d-flex align-items-center font-weight-bold">
                    <i class="feather icon-edit me-2 font-size-lg"></i> Edit Kategori
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; outline: none; opacity: 0.9; cursor: pointer;" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="feather icon-x" style="font-size: 16px;"></i>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-dark mb-2">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-control form-control-lg rounded-lg border" required style="font-size: 0.95rem;">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 rounded-pill shadow-sm">
                        <i class="feather icon-save me-1"></i> Update Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title text-white d-flex align-items-center font-weight-bold">
                    <i class="feather icon-trash-2 me-2 font-size-lg"></i> Hapus Kategori
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; outline: none; opacity: 0.9; cursor: pointer;" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="feather icon-x" style="font-size: 16px;"></i>
                </button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger">
                    <i class="feather icon-alert-circle" style="font-size: 48px;"></i>
                </div>
                <p class="mb-1 text-secondary">Apakah Anda yakin ingin menghapus kategori ini?</p>
                <h5 class="font-weight-bold text-dark mt-2" id="delete-name-display"></h5>
            </div>
            <div class="modal-footer bg-light py-3 px-4 justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4 rounded-pill shadow-sm">
                        <i class="feather icon-trash-2 me-1"></i> Ya, Hapus
                    </button>
                </form>
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
            $('#categories-table').DataTable({
                pageLength: 10,
                order: [[1, 'asc']],
                language: { emptyTable: 'Belum ada data Kategori.' },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [2] }
                ]
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.btn-edit');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const name = this.getAttribute('data-name');
                    const action = this.getAttribute('data-action');
                    document.getElementById('edit-name').value = name;
                    document.getElementById('editForm').action = action;
                    $('#editCategoryModal').modal('show');
                });
            });

            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const name = this.getAttribute('data-name');
                    const action = this.getAttribute('data-action');
                    document.getElementById('delete-name-display').textContent = name;
                    document.getElementById('deleteForm').action = action;
                    $('#deleteCategoryModal').modal('show');
                });
            });
        });
    </script>
@endpush
