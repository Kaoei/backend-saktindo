@extends('layouts.dashboard',
[
    'title' => 'Tambah Warehouse Task',
    'pageTitle' => 'Warehouse Task',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('warehouse-task.index').'">Warehouse Task</a></li><li class="breadcrumb-item">Tambah Warehouse Task</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-1">Tambah Warehouse Task</h5>
                    <small class="text-muted">Buat task gudang dari sales order dan invoice</small>
                </div>

                <a href="{{ route('warehouse-task.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>

            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('warehouse-task.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Invoice <span class="text-danger">*</span>
                            </label>

                            <select name="invoice_id"
                                    class="form-select"
                                    required>

                                <option value="">Pilih Invoice</option>

                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}"
                                        {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                        {{ $invoice->id }} - {{ $invoice->invoice_number }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                PIC Gudang
                            </label>

                            <input type="text"
                                   name="assigned_to"
                                   class="form-control"
                                   value="{{ old('assigned_to') }}"
                                   placeholder="Masukkan nama PIC gudang">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                            </label>

                            <input type="text"
                                   class="form-control"
                                   value="waiting"
                                   disabled>

                            <small class="text-muted">
                                Status otomatis waiting saat task dibuat
                            </small>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Note
                            </label>

                            <textarea name="note"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Catatan task gudang">{{ old('note') }}</textarea>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">

                        <button type="submit"
                                class="btn btn-primary">
                            Simpan Warehouse Task
                        </button>

                        <a href="{{ route('warehouse-task.index') }}"
                           class="btn btn-outline-secondary">
                            Batal
                        </a>

                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection