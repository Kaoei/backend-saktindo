@extends('layouts.dashboard',
[
    'title' => 'Pelunasan Piutang',
    'pageTitle' => 'Pelunasan Piutang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('finance.receivables').'">Piutang Usaha</a></li><li class="breadcrumb-item">Pelunasan Piutang</li>'
])

@section('content')

<div class="row">

    <div class="col-md-8">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Form Pelunasan Piutang</h5>
                <small class="text-muted">Input pembayaran customer untuk invoice ini</small>
            </div>

            <form action="{{ route('finance.payment.store', $invoice->id) }}" method="POST">
                @csrf

                <div class="card-body">

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Tanggal Bayar</label>
                        <input type="date"
                               name="payment_date"
                               class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', date('Y-m-d')) }}">
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="method"
                                class="form-select @error('method') is-invalid @enderror">
                            <option value="">Pilih Metode</option>
                            <option value="cash" {{ old('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="transfer_bank" {{ old('method') == 'transfer_bank' ? 'selected' : '' }}>Transfer Bank</option>
                            <option value="qris" {{ old('method') == 'qris' ? 'selected' : '' }}>QRIS</option>
                            <option value="giro" {{ old('method') == 'giro' ? 'selected' : '' }}>Giro</option>
                        </select>
                        @error('method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rekening Penerimaan</label>
                        <select name="receiving_account"
                                class="form-select @error('receiving_account') is-invalid @enderror">
                            <option value="">Pilih Rekening</option>
                            <option value="js" {{ old('receiving_account') == 'js' ? 'selected' : '' }}>JS</option>
                            <option value="sjb" {{ old('receiving_account') == 'sjb' ? 'selected' : '' }}>SJB</option>
                        </select>
                        @error('receiving_account')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal Bayar</label>
                        <input type="number"
                               name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', $invoice->outstanding_amount) }}"
                               min="1"
                               max="{{ $invoice->outstanding_amount }}"
                               step="0.01">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Maksimal pembayaran:
                            Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Referensi</label>
                        <input type="text"
                               name="reference_number"
                               class="form-control @error('reference_number') is-invalid @enderror"
                               value="{{ old('reference_number') }}"
                               placeholder="Contoh: No transfer / giro">
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  rows="3"
                                  placeholder="Catatan tambahan">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="card-footer text-end">
                    <a href="{{ route('finance.show', $invoice->id) }}"
                       class="btn btn-secondary">
                        Batal
                    </a>

                    <button type="submit"
                            class="btn btn-success">
                        Simpan Pembayaran
                    </button>
                </div>

            </form>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Ringkasan Invoice</h5>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <small class="text-muted">No Invoice</small>
                    <h6>{{ $invoice->invoice_number }}</h6>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Customer</small>
                    <h6>{{ $invoice->salesOrder->customer_name ?? '-' }}</h6>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Tanggal Invoice</small>
                    <h6>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</h6>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Jatuh Tempo</small>
                    <h6>
                        {{ $invoice->due_date
                            ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y')
                            : '-'
                        }}
                    </h6>
                </div>

                <hr>

                <div class="mb-3">
                    <small class="text-muted">Total Tagihan</small>
                    <h5>
                        Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                    </h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Sudah Terbayar</small>
                    <h5 class="text-success">
                        Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                    </h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Sisa Piutang</small>
                    <h5 class="text-danger">
                        Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}
                    </h5>
                </div>

                <hr>

                <div class="mb-3">
                    <small class="text-muted">Status</small>
                    <div>
                        @if($invoice->status == 'paid')
                            <span class="badge bg-success">Lunas</span>
                        @elseif($invoice->status == 'outstanding')
                            <span class="badge bg-warning">Outstanding</span>
                        @else
                            <span class="badge bg-secondary">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

@endsection