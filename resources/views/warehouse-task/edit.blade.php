@extends('layouts.dashboard',
[
    'title' => 'Edit Warehouse Task',
    'pageTitle' => 'Warehouse Task',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('warehouse-task.index').'">Warehouse Task</a></li><li class="breadcrumb-item">Edit Warehouse Task</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="card">

            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Edit Warehouse Task: {{ $warehouseTask->id }}</h5>
                    <small class="text-muted">
                        Status:
                        <span class="font-monospace">{{ $warehouseTask->status }}</span>
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

                <form method="POST" action="{{ route('warehouse-task.update', $warehouseTask->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID Task</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $warehouseTask->id }}"
                                   disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Sales Order <span class="text-danger">*</span>
                            </label>

                            <select name="sales_order_id"
                                    class="form-select @error('sales_order_id') is-invalid @enderror"
                                    required>
                                <option value="">Pilih Sales Order</option>

                                @foreach($salesOrders as $salesOrder)
                                    <option value="{{ $salesOrder->id }}"
                                        {{ old('sales_order_id', $warehouseTask->sales_order_id) == $salesOrder->id ? 'selected' : '' }}>
                                        {{ $salesOrder->id }}
                                    </option>
                                @endforeach
                            </select>

                            @error('sales_order_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Invoice <span class="text-danger">*</span>
                            </label>

                            <select name="invoice_id"
                                    class="form-select @error('invoice_id') is-invalid @enderror"
                                    required>
                                <option value="">Pilih Invoice</option>

                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}"
                                        {{ old('invoice_id', $warehouseTask->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                        {{ $invoice->id }} - {{ $invoice->invoice_number }}
                                    </option>
                                @endforeach
                            </select>

                            @error('invoice_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                PIC Gudang
                            </label>

                            <input type="text"
                                   name="assigned_to"
                                   class="form-control @error('assigned_to') is-invalid @enderror"
                                   value="{{ old('assigned_to', $warehouseTask->assigned_to) }}"
                                   placeholder="Masukkan nama PIC gudang">

                            @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>

                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    required>
                                <option value="waiting" {{ old('status', $warehouseTask->status) == 'waiting' ? 'selected' : '' }}>
                                    Waiting
                                </option>

                                <option value="process" {{ old('status', $warehouseTask->status) == 'process' ? 'selected' : '' }}>
                                    Process
                                </option>

                                <option value="completed" {{ old('status', $warehouseTask->status) == 'completed' ? 'selected' : '' }}>
                                    Completed
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Note
                            </label>

                            <textarea name="note"
                                      class="form-control @error('note') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Catatan task gudang">{{ old('note', $warehouseTask->note) }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            Update Warehouse Task
                        </button>

                        <a href="{{ route('warehouse-task.index') }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

@endsection