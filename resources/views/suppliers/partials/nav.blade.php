@php
    $items = [
        ['route' => 'suppliers.index', 'label' => 'Data Vendor', 'icon' => 'business'],
        ['route' => 'suppliers.products', 'label' => 'Barang Supplier', 'icon' => 'inventory_2'],
        ['route' => 'suppliers.contacts', 'label' => 'Kontak Supplier', 'icon' => 'contacts'],
        ['route' => 'suppliers.purchases', 'label' => 'Riwayat Pembelian', 'icon' => 'receipt_long'],
        ['route' => 'suppliers.payment-terms', 'label' => 'Termin Pembayaran', 'icon' => 'payments'],
    ];
@endphp

<div class="card mb-3 mt-5">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="btn-group flex-wrap" role="group">
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}" class="btn {{ request()->routeIs($item['route']) ? 'btn-primary' : 'btn-outline-secondary' }}">
                        <i class="material-icons-two-tone {{ request()->routeIs($item['route']) ? 'text-white' : '' }}" style="font-size: 16px;">{{ $item['icon'] }}</i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
            @if(auth()->user()?->hasPermission('suppliers.create'))
                <a href="{{ route('suppliers.create') }}" class="btn btn-success">
                    <i class="material-icons-two-tone text-white" style="font-size: 16px;">add_business</i>
                    Tambah Supplier
                </a>
            @endif
        </div>
    </div>
</div>
