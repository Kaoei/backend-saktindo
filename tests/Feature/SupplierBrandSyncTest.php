<?php

use App\Models\Brand;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('brand is automatically synced to master brand when creating supplier with brand name', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $response = $this->actingAs($user)->post(route('suppliers.store'), [
        'name' => 'PT Supplier Maju',
        'company_name' => 'Brand Merek Bagus',
        'status' => 'active',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('suppliers', [
        'name' => 'PT Supplier Maju',
        'company_name' => 'Brand Merek Bagus',
    ]);

    $this->assertDatabaseHas('brands', [
        'name' => 'Brand Merek Bagus',
    ]);
});

test('brand sync is case-insensitive and avoids creating duplicate master brand', function () {
    Brand::create(['name' => 'Philips']);

    $supplier = Supplier::create([
        'name' => 'PT Distribusi Listrik',
        'company_name' => 'philips',
        'status' => 'active',
    ]);

    $this->assertEquals(1, Brand::whereRaw('LOWER(name) = ?', ['philips'])->count());
    $this->assertEquals('Philips', Brand::whereRaw('LOWER(name) = ?', ['philips'])->first()->name);
});

test('supplier product brand is automatically synced to master brand', function () {
    $supplier = Supplier::create([
        'name' => 'PT Supplier Alat',
        'status' => 'active',
    ]);

    SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Lampu LED 10W',
        'brand' => 'Brand Baru Unik',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('brands', [
        'name' => 'Brand Baru Unik',
    ]);
});
