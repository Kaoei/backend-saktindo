@extends('layouts.dashboard',
[
    'title' => 'Tambah Barang Keluar',
    'pageTitle' => 'Barang Keluar',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('warehouse-task.index').'">Warehouse Task</a></li><li class="breadcrumb-item">Tambah Barang Keluar</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-1">Tambah Barang Keluar</h5>
                    <small class="text-muted">
                        Proses pengeluaran barang dari task gudang
                    </small>
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

                <div class="alert alert-info">
                    <strong>Task Gudang:</strong> {{ $warehouseTask->id }}
                    <br>
                    <strong>Invoice:</strong>
                    {{ $warehouseTask->invoice->invoice_number ?? '-' }}
                    <br>
                    <strong>PIC Gudang:</strong>
                    {{ $warehouseTask->assigned_to ?? '-' }}
                </div>

                <form method="POST" action="{{ route('outbound.store') }}">
                    @csrf

                    <input type="hidden"
                           name="warehouse_task_id"
                           value="{{ $warehouseTask->id }}">

                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Barang Gudang <span class="text-danger">*</span>
                            </label>

                            <select name="gudang_product_id"
                                    class="form-select"
                                    required>
                                <option value="">Pilih Barang Gudang</option>

                                @foreach ($gudangProducts as $gudangProduct)
                                    <option value="{{ $gudangProduct->id }}"
                                        {{ old('gudang_product_id') == $gudangProduct->id ? 'selected' : '' }}>
                                        {{ $gudangProduct->supplierProduct->item_name ?? '-' }}
                                        |
                                        SKU: {{ $gudangProduct->supplierProduct->sku ?? '-' }}
                                        |
                                        Rack: {{ $gudangProduct->rack->rak_kode ?? '-' }}
                                        |
                                        Stok: {{ $gudangProduct->qty }}
                                    </option>
                                @endforeach
                            </select>

                            <small class="text-muted">
                                Pilih barang dari stok gudang yang tersedia.
                            </small>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Qty Keluar <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="qty"
                                   class="form-control"
                                   value="{{ old('qty') }}"
                                   min="1"
                                   placeholder="Masukkan qty keluar"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Tanggal Keluar <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="outbound_date"
                                   class="form-control"
                                   value="{{ old('outbound_date', date('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Delivery Type <span class="text-danger">*</span>
                            </label>

                            <select name="delivery_type"
                                    class="form-select"
                                    required>
                                <option value="">Pilih Delivery Type</option>
                                <option value="full" {{ old('delivery_type') == 'full' ? 'selected' : '' }}>
                                    Full Delivery
                                </option>
                                <option value="partial" {{ old('delivery_type') == 'partial' ? 'selected' : '' }}>
                                    Partial Delivery
                                </option>
                            </select>
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
                                      placeholder="Catatan barang keluar">{{ old('note') }}</textarea>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">

                        <button type="submit"
                                class="btn btn-primary">
                            Simpan Barang Keluar
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