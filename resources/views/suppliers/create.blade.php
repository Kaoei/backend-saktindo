@extends('layouts.dashboard', [
    'title' => 'Tambah Supplier',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Tambah Supplier</li>',
])

@section('content')
    @include('suppliers.form', [
        'action' => route('suppliers.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Supplier',
    ])
@endsection
