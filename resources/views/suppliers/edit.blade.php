@extends('layouts.dashboard', [
    'title' => 'Edit Supplier',
    'pageTitle' => 'Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('suppliers.index').'">Supplier</a></li><li class="breadcrumb-item">Edit Supplier</li>',
])

@section('content')
    @include('suppliers.form', [
        'action' => route('suppliers.update', $supplier),
        'method' => 'PUT',
        'submitLabel' => 'Update Supplier',
    ])
@endsection
