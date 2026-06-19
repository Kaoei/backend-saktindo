@extends('layouts.dashboard', [
    'title' => 'Detail Supplier',
    'pageTitle' => 'Master Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Detail</li>',
])

@section('content')
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-start justify-content-between">
                <div>
                    <h5 class="mb-1">{{ $supplier->name }}</h5>
                    <span class="badge bg-light-secondary font-monospace">{{ $supplier->id }}</span>
                </div>
                <span class="badge {{ $supplier->status === 'blocked' ? 'bg-light-danger' : ($supplier->status === 'inactive' ? 'bg-light-warning' : 'bg-light-success') }}">{{ ucfirst($supplier->status) }}</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Perusahaan</div>
                    <div class="fw-semibold">{{ $supplier->company_name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Tipe Vendor</div>
                    <div class="fw-semibold">{{ $supplier->vendor_type ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">PIC Utama</div>
                    <div class="fw-semibold">{{ $supplier->pic_name ?: '-' }}</div>
                    <div class="text-muted small">{{ $supplier->pic_phone ?: '-' }}{{ $supplier->pic_email ? ' / '.$supplier->pic_email : '' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Kontak Perusahaan</div>
                    <div>{{ $supplier->phone ?: '-' }}{{ $supplier->email ? ' / '.$supplier->email : '' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">NPWP</div>
                    <div>{{ $supplier->tax_number ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Termin Default</div>
                    <div>{{ $supplier->payment_due_days }} hari</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Limit Hutang</div>
                    <div>Rp {{ number_format((float) $supplier->debt_limit, 0, ',', '.') }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Rekening</div>
                    <div>{{ collect([$supplier->bank_name, $supplier->bank_account_number, $supplier->bank_account_name])->filter()->implode(' - ') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Alamat</div>
                    <div>{{ $supplier->address ?: '-' }}</div>
                    <div class="text-muted small">{{ collect([$supplier->city, $supplier->province, $supplier->postal_code])->filter()->implode(', ') }}</div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                @if(auth()->user()?->hasPermission('suppliers.edit'))
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">Edit Supplier</a>
                @endif
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body">
                    <div class="text-muted small">Barang</div>
                    <h4 class="mb-0">{{ $supplier->products->count() }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body">
                    <div class="text-muted small">Kontak</div>
                    <h4 class="mb-0">{{ $supplier->contacts->count() }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body">
                    <div class="text-muted small">Pembelian</div>
                    <h4 class="mb-0">{{ $supplier->purchaseHistories->count() }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body">
                    <div class="text-muted small">Termin</div>
                    <h4 class="mb-0">{{ $supplier->paymentTerms->count() }}</h4>
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Barang Supplier</h5>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.products.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-primary">Tambah Barang</a>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover m-b-0">
                    <thead><tr><th>SKU / Part</th><th>Barang</th><th>Harga Terakhir</th><th>Lead Time</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($supplier->products as $product)
                            <tr>
                                <td><span class="font-monospace">{{ $product->sku ?: '-' }}</span><div class="small text-muted">{{ $product->part_number ?: '-' }}</div></td>
                                <td>{{ $product->item_name }}<div class="small text-muted">{{ collect([$product->brand, $product->category, $product->unit])->filter()->implode(' / ') }}</div></td>
                                <td>Rp {{ number_format((float) $product->last_purchase_price, 0, ',', '.') }}</td>
                                <td>{{ $product->lead_time_days }} hari</td>
                                <td><span class="badge {{ $product->status === 'active' ? 'bg-light-success' : 'bg-light-warning' }}">{{ ucfirst($product->status) }}</span></td>
                                <td class="text-end">
                                    @if(auth()->user()?->hasPermission('suppliers.edit'))
                                        <a href="{{ route('suppliers.products.edit', $product) }}" class="text-success"><i class="feather icon-edit f-16 text-success"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada barang supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Kontak Supplier</h5>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.contacts.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-primary">Tambah Kontak</a>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover m-b-0">
                    <thead><tr><th>Nama</th><th>Jabatan</th><th>Email</th><th>Telepon</th><th>Utama</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($supplier->contacts as $contact)
                            <tr>
                                <td>{{ $contact->name }}</td><td>{{ $contact->position ?: '-' }}</td><td>{{ $contact->email ?: '-' }}</td><td>{{ $contact->phone ?: '-' }}</td><td>{{ $contact->is_primary ? 'Ya' : '-' }}</td>
                                <td class="text-end">
                                    @if(auth()->user()?->hasPermission('suppliers.edit'))
                                        <a href="{{ route('suppliers.contacts.edit', $contact) }}" class="text-success"><i class="feather icon-edit f-16 text-success"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada kontak supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Riwayat Pembelian</h5>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-primary">Tambah Riwayat</a>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover m-b-0">
                    <thead><tr><th>Tanggal</th><th>Invoice</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($supplier->purchaseHistories as $history)
                            <tr>
                                <td>{{ $history->purchase_date?->format('Y-m-d') }}</td><td>{{ $history->invoice_number ?: '-' }}</td><td>{{ $history->item_name }}</td><td>{{ number_format((float) $history->quantity, 2) }}</td><td>Rp {{ number_format((float) $history->total_amount, 0, ',', '.') }}</td><td>{{ ucfirst($history->status) }}</td>
                                <td class="text-end">
                                    @if(auth()->user()?->hasPermission('suppliers.edit'))
                                        <a href="{{ route('suppliers.purchases.edit', $history) }}" class="text-success"><i class="feather icon-edit f-16 text-success"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada riwayat pembelian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Termin Pembayaran</h5>
                @if(auth()->user()?->hasPermission('suppliers.create'))
                    <a href="{{ route('suppliers.payment-terms.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-primary">Tambah Termin</a>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover m-b-0">
                    <thead><tr><th>Nama</th><th>Jatuh Tempo</th><th>DP</th><th>Denda</th><th>Default</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($supplier->paymentTerms as $term)
                            <tr>
                                <td>{{ $term->name }}</td><td>{{ $term->due_days }} hari</td><td>{{ number_format((float) $term->down_payment_percent, 2) }}%</td><td>{{ number_format((float) $term->late_fee_percent, 2) }}%</td><td>{{ $term->is_default ? 'Ya' : '-' }}</td>
                                <td class="text-end">
                                    @if(auth()->user()?->hasPermission('suppliers.edit'))
                                        <a href="{{ route('suppliers.payment-terms.edit', $term) }}" class="text-success"><i class="feather icon-edit f-16 text-success"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada termin pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
