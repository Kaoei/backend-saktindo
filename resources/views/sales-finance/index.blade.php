@extends('layouts.dashboard', [
    'title' => 'Sales & Finance',
    'pageTitle' => 'Sales & Finance Flow',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item">Sales & Finance</li>',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | NORMALISASI INVOICE
    |--------------------------------------------------------------------------
    */

    $allInvoices = collect();

    foreach ($orders as $order) {
        $collection = collect($order->invoices_collection ?? []);

        foreach ($collection as $inv) {
            if ($inv) {
                $allInvoices->push($inv);
            }
        }

        if ($order->invoice_record) {
            $allInvoices->push($order->invoice_record);
        }
    }

    $allInvoices = $allInvoices
        ->filter()
        ->unique('id')
        ->values();


    /*
    |--------------------------------------------------------------------------
    | CARI INVOICE GABUNGAN
    |--------------------------------------------------------------------------
    */

    $combinedInvoices = $allInvoices->filter(function ($invoice) {
        $type = strtolower(trim((string) ($invoice->invoice_type ?? '')));

        return in_array($type, [
            'gabungan',
            'combined',
            'combination',
            'merge',
            'merged',
        ], true);
    })->values();


    /*
    |--------------------------------------------------------------------------
    | INVOICE YANG AKAN DITAMPILKAN
    |
    | Jika ada invoice gabungan, invoice normal yang berhubungan
    | dengan merge tidak dihitung lagi sebagai invoice outstanding.
    |--------------------------------------------------------------------------
    */

    $displayInvoices = collect();

    foreach ($orders as $order) {

        $orderInvoices = collect($order->invoices_collection ?? [])
            ->filter();

        if ($order->invoice_record) {
            $orderInvoices->push($order->invoice_record);
        }

        $orderInvoices = $orderInvoices
            ->unique('id')
            ->values();

        $combined = $orderInvoices->first(function ($invoice) {
            $type = strtolower(trim((string) ($invoice->invoice_type ?? '')));

            return in_array($type, [
                'gabungan',
                'combined',
                'combination',
                'merge',
                'merged',
            ], true);
        });

        if ($combined) {
            $displayInvoices->push($combined);
            continue;
        }

        $normal = $orderInvoices->first(function ($invoice) {
            $type = strtolower(trim((string) ($invoice->invoice_type ?? '')));

            return !in_array($type, [
                'gabungan',
                'combined',
                'combination',
                'merge',
                'merged',
            ], true);
        });

        if ($normal) {
            $displayInvoices->push($normal);
        }
    }

    $displayInvoices = $displayInvoices
        ->filter()
        ->unique('id')
        ->values();


    /*
    |--------------------------------------------------------------------------
    | INVOICE OUTSTANDING
    |--------------------------------------------------------------------------
    */

    $outstandingInvoices = $displayInvoices->filter(function ($invoice) {
        return strtolower(trim((string) ($invoice->status ?? ''))) === 'outstanding';
    });


    /*
    |--------------------------------------------------------------------------
    | TOTAL OUTSTANDING
    |--------------------------------------------------------------------------
    */

    $outstanding = $outstandingInvoices->sum(function ($invoice) {
        return (float) ($invoice->outstanding_amount ?? 0);
    });


    /*
    |--------------------------------------------------------------------------
    | TOTAL SALES ORDER
    |
    | Untuk dashboard, invoice gabungan dihitung sebagai satu transaksi
    | dan tidak menyebabkan invoice/order gabungan dihitung berulang.
    |--------------------------------------------------------------------------
    */

    $totalSalesOrder = $orders
        ->filter()
        ->count();


    /*
    |--------------------------------------------------------------------------
    | PENDING STOCK
    |--------------------------------------------------------------------------
    */

    $pendingStock = $orders
        ->where('stock_status', 'pending')
        ->count();

@endphp


<div class="row mt-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Sales Order</div>
                <h3 class="mb-0">{{ $totalSalesOrder }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Pending Stock</div>
                <h3 class="mb-0">{{ $pendingStock }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Invoice Outstanding</div>
                <h3 class="mb-0">{{ $outstandingInvoices->count() }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Outstanding</div>
                <h5 class="mb-0">
                    Rp {{ number_format($outstanding, 0, ',', '.') }}
                </h5>
            </div>
        </div>
    </div>
</div>


@if(auth()->user()?->hasPermission('sales_finance.create'))

<form
    method="POST"
    action="{{ route('sales-finance.invoice.consolidate') }}"
    class="card shadow-sm border-0 mb-4"
    style="border-radius: 10px; overflow: hidden;"
>
    @csrf

    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-1 fw-bold text-dark d-flex align-items-center">
            <i class="feather icon-layers text-primary me-2 fs-5"></i>
            Invoice Gabungan Bulanan
        </h5>

        <small class="text-muted">
            Gabungkan beberapa Sales Order dari customer yang sama dalam satu periode transaksi.
        </small>
    </div>

    <div class="card-body p-4 bg-light">

        <div class="row g-3">

            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold text-secondary small">
                    Customer
                </label>

                <select
                    name="customer_id"
                    class="form-select bg-white"
                    required
                >
                    <option value="">-- Pilih Customer --</option>

                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->nama_customer }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-lg-2 col-md-6">
                <label class="form-label fw-bold text-secondary small">
                    Periode Awal
                </label>

                <input
                    type="date"
                    name="period_start"
                    class="form-control bg-white"
                    value="{{ now()->startOfMonth()->toDateString() }}"
                    required
                >
            </div>


            <div class="col-lg-2 col-md-6">
                <label class="form-label fw-bold text-secondary small">
                    Periode Akhir
                </label>

                <input
                    type="date"
                    name="period_end"
                    class="form-control bg-white"
                    value="{{ now()->endOfMonth()->toDateString() }}"
                    required
                >
            </div>


            <div class="col-lg-2 col-md-6">
                <label class="form-label fw-bold text-secondary small">
                    Jenis Faktur
                </label>

                <select
                    name="tax_type"
                    class="form-select bg-white"
                    required
                >
                    <option value="js">JS</option>
                    <option value="sjb_non_pajak">SJB Non Pajak</option>
                    <option value="sjb_pajak">SJB Pajak</option>
                </select>
            </div>


            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold text-secondary small">
                    Tanggal Invoice
                </label>

                <input
                    type="date"
                    name="invoice_date"
                    class="form-control bg-white"
                    value="{{ now()->toDateString() }}"
                    required
                >
            </div>

            <input
                type="date"
                name="due_date"
                class="form-control d-none"
            >

        </div>

    </div>


    <div class="card-footer bg-white py-3 px-4 text-end border-top">

        <button
            type="submit"
            class="btn btn-success px-4 py-2 rounded-pill shadow-sm fw-semibold"
        >
            <i class="feather icon-plus-circle me-1"></i>
            Buat Invoice Gabungan
        </button>

    </div>

</form>

@endif


<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h5 class="mb-0">
                        Follow Up Sales
                    </h5>
                </div>


                @if(auth()->user()?->hasPermission('sales_finance.create'))

                    <a
                        href="{{ route('sales-finance.create') }}"
                        class="btn btn-primary"
                    >
                        <i class="material-icons-two-tone text-white">
                            add_shopping_cart
                        </i>

                        Input SO
                    </a>

                @endif

            </div>


            <div class="card-body">

                @if(session('status'))

                    <div
                        class="alert alert-success alert-dismissible fade show"
                        role="alert"
                    >
                        {{ session('status') }}

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                        ></button>
                    </div>

                @endif


                <div class="table-responsive">

                    <table
                        id="sales-finance-table"
                        class="table table-hover m-b-0"
                    >

                        <thead>

                            <tr>

                                <th>Sales Order</th>

                                <th>Customer</th>

                                <th>PO</th>

                                <th>Status Order</th>

                                <th>Stok</th>

                                <th>Invoice</th>

                                <th class="text-end">
                                    Total
                                </th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        @forelse($orders as $order)

                            @php

                                /*
                                |--------------------------------------------------------------------------
                                | AMBIL SEMUA INVOICE MILIK ORDER
                                |--------------------------------------------------------------------------
                                */

                                $orderInvoices = collect(
                                    $order->invoices_collection ?? []
                                )->filter();

                                if ($order->invoice_record) {
                                    $orderInvoices->push(
                                        $order->invoice_record
                                    );
                                }

                                $orderInvoices = $orderInvoices
                                    ->unique('id')
                                    ->values();


                                /*
                                |--------------------------------------------------------------------------
                                | CARI INVOICE GABUNGAN
                                |--------------------------------------------------------------------------
                                */

                                $combinedInvoice = $orderInvoices->first(
                                    function ($inv) {

                                        $type = strtolower(
                                            trim(
                                                (string) (
                                                    $inv->invoice_type ?? ''
                                                )
                                            )
                                        );

                                        return in_array(
                                            $type,
                                            [
                                                'gabungan',
                                                'combined',
                                                'combination',
                                                'merge',
                                                'merged',
                                            ],
                                            true
                                        );
                                    }
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | INVOICE NORMAL
                                |--------------------------------------------------------------------------
                                */

                                $normalInvoice = $orderInvoices->first(
                                    function ($inv) {

                                        $type = strtolower(
                                            trim(
                                                (string) (
                                                    $inv->invoice_type ?? ''
                                                )
                                            )
                                        );

                                        return !in_array(
                                            $type,
                                            [
                                                'gabungan',
                                                'combined',
                                                'combination',
                                                'merge',
                                                'merged',
                                            ],
                                            true
                                        );
                                    }
                                );


                                /*
                                |--------------------------------------------------------------------------
                                | PRIORITASKAN INVOICE GABUNGAN
                                |--------------------------------------------------------------------------
                                */

                                $invoice = $combinedInvoice ?: $normalInvoice;


                                /*
                                |--------------------------------------------------------------------------
                                | CEK APAKAH ORDER DIALIHKAN KE MERGE
                                |--------------------------------------------------------------------------
                                */

                                $isRedirectedToCombined =
                                    $combinedInvoice &&
                                    $normalInvoice &&
                                    $combinedInvoice->id !== $normalInvoice->id;


                            @endphp


                            <tr>

                                <td class="align-middle">

                                    <span class="badge bg-light-secondary font-monospace">
                                        {{ $order->id }}
                                    </span>

                                </td>


                                <td class="align-middle">

                                    {{ $order->customer_name }}

                                </td>


                                <td class="align-middle">

                                    {{ $order->customer_po_number }}

                                    <div class="small text-muted">

                                        {{
                                            optional($order->po_date)->format('d M Y')
                                            ?: '-'
                                        }}

                                    </div>

                                </td>


                                <td class="align-middle">

                                    <span class="badge bg-light-primary">

                                        {{
                                            str_replace(
                                                '_',
                                                ' ',
                                                ucfirst(
                                                    $order->order_status
                                                )
                                            )
                                        }}

                                    </span>

                                </td>


                                <td class="align-middle">

                                    <span
                                        class="badge {{
                                            $order->stock_status === 'pending'
                                                ? 'bg-light-warning'
                                                : (
                                                    $order->stock_status === 'available'
                                                        ? 'bg-light-success'
                                                        : 'bg-light-secondary'
                                                )
                                        }}"
                                    >

                                        {{
                                            str_replace(
                                                '_',
                                                ' ',
                                                ucfirst(
                                                    $order->stock_status
                                                )
                                            )
                                        }}

                                    </span>


                                    @if($order->warehouse_task_reference)

                                        <div class="small text-muted">

                                            {{ $order->warehouse_task_reference }}

                                        </div>

                                    @endif

                                </td>


                                {{-- ========================================================= --}}
                                {{-- INVOICE --}}
                                {{-- ========================================================= --}}

                                <td class="align-middle">

                                    @if($isRedirectedToCombined)

                                        {{-- INVOICE NORMAL SUDAH DIALIHKAN KE MERGE --}}

                                        <div class="d-flex flex-column">

                                            <span
                                                class="badge bg-light-warning text-warning mb-1"
                                                style="width: fit-content;"
                                            >
                                                Dialihkan ke Invoice Gabungan
                                            </span>


                                            <a
                                                href="{{ route('sales-finance.show', $order) }}"
                                                class="fw-semibold text-primary"
                                            >
                                                {{ $combinedInvoice->invoice_number }}
                                            </a>


                                            <div class="small text-muted">

                                                Gabungan /
                                                {{
                                                    ucfirst(
                                                        $combinedInvoice->status
                                                    )
                                                }}

                                            </div>


                                            @if($combinedInvoice->warehouseTask)

                                                <div class="small text-muted">

                                                    Task:
                                                    {{
                                                        $combinedInvoice
                                                            ->warehouseTask
                                                            ->id
                                                    }}

                                                    /

                                                    {{
                                                        ucfirst(
                                                            $combinedInvoice
                                                                ->warehouseTask
                                                                ->status
                                                        )
                                                    }}

                                                </div>

                                            @endif

                                        </div>


                                    @elseif($combinedInvoice)

                                        {{-- INVOICE GABUNGAN --}}

                                        <div class="d-flex flex-column">

                                            <span
                                                class="badge bg-light-success text-success mb-1"
                                                style="width: fit-content;"
                                            >
                                                Invoice Gabungan
                                            </span>


                                            <a
                                                href="{{ route('sales-finance.show', $order) }}"
                                                class="fw-semibold text-primary"
                                            >
                                                {{ $combinedInvoice->invoice_number }}
                                            </a>


                                            <div class="small text-muted">

                                                Gabungan /
                                                {{
                                                    ucfirst(
                                                        $combinedInvoice->status
                                                    )
                                                }}

                                            </div>


                                            @if($combinedInvoice->warehouseTask)

                                                <div class="small text-muted">

                                                    Task:
                                                    {{
                                                        $combinedInvoice
                                                            ->warehouseTask
                                                            ->id
                                                    }}

                                                    /

                                                    {{
                                                        ucfirst(
                                                            $combinedInvoice
                                                                ->warehouseTask
                                                                ->status
                                                        )
                                                    }}

                                                </div>

                                            @endif

                                        </div>


                                    @elseif($normalInvoice)

                                        {{-- INVOICE NORMAL --}}

                                        <a
                                            href="{{ route('sales-finance.show', $order) }}"
                                            class="fw-semibold text-primary"
                                        >
                                            {{ $normalInvoice->invoice_number }}
                                        </a>


                                        <div class="small text-muted">

                                            {{
                                                ucfirst(
                                                    $normalInvoice->invoice_type
                                                )
                                            }}

                                            /

                                            {{
                                                ucfirst(
                                                    $normalInvoice->status
                                                )
                                            }}

                                        </div>


                                        @if($normalInvoice->warehouseTask)

                                            <div class="small text-muted">

                                                Task:
                                                {{
                                                    $normalInvoice
                                                        ->warehouseTask
                                                        ->id
                                                }}

                                                /

                                                {{
                                                    ucfirst(
                                                        $normalInvoice
                                                            ->warehouseTask
                                                            ->status
                                                    )
                                                }}

                                            </div>

                                        @endif


                                    @else

                                        <span class="text-muted">
                                            Belum ada
                                        </span>

                                    @endif

                                </td>


                                <td class="align-middle text-end">

                                    Rp
                                    {{
                                        number_format(
                                            (float) $order->grand_total,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}

                                </td>


                                <td class="align-middle text-end">

                                    <a
                                        href="{{ route('sales-finance.show', $order) }}"
                                        class="text-primary"
                                        title="Detail"
                                    >

                                        <i class="feather icon-eye f-16 text-primary"></i>

                                    </a>


                                    @if(auth()->user()?->hasPermission('sales_finance.edit'))

                                        <a
                                            href="{{ route('sales-finance.edit', $order) }}"
                                            class="text-success ms-2"
                                            title="Edit"
                                        >

                                            <i class="feather icon-edit f-16 text-success"></i>

                                        </a>

                                    @endif

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

@endsection


@push('scripts')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>


<script>

    $(function () {

        $('#sales-finance-table').DataTable({

            pageLength: 10,

            order: [[0, 'desc']],

            language: {
                emptyTable: 'Belum ada data Sales.'
            },

            columnDefs: [
                {
                    orderable: false,
                    searchable: false,
                    targets: [7]
                }
            ]

        });

    });

</script>

@endpush