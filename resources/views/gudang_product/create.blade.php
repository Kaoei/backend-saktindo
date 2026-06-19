@extends('layouts.dashboard',
[
    'title' => 'Simpan Barang ke Rak',
    'pageTitle' => 'Produk Gudang',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('gudang-product.index').'">Produk Gudang</a></li><li class="breadcrumb-item">Simpan ke Rak</li>'
])

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-8">

        <div class="card">

            <div class="card-header">
                <h5>Simpan Barang ke Rak</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('gudang-product.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">
                            Barang Masuk (Pending)
                        </label>

                        <select name="inbound_id"
                                class="form-select"
                                required>

                            <option value="">
                                Pilih Barang Masuk
                            </option>

                            @foreach($inbounds as $inbound)
                                <option value="{{ $inbound->id }}">
                                    {{ $inbound->id }}
                                    -
                                    {{ $inbound->supplierProduct->item_name }}
                                    (Qty: {{ $inbound->qty_received }})
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Rak Penyimpanan
                        </label>

                        <select name="rack_id"
                                class="form-select"
                                required>

                            <option value="">
                                Pilih Rak
                            </option>

                            @foreach($racks as $rack)
                                <option value="{{ $rack->rak_kode }}">
                                    {{ $rack->rak_kode }}
                                    -
                                    {{ $rack->location }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <button type="submit"
                            class="btn btn-primary">
                        Simpan ke Gudang
                    </button>

                    <a href="{{ route('gudang-product.index') }}"
                       class="btn btn-secondary">
                        Batal
                    </a>

                </form>

            </div>

        </div>

    </div>
</div>

@endsection