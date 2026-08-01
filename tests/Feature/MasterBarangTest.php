<?php

use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin can manage master brands', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    // Index
    $this->actingAs($superAdmin)
        ->get(route('brands.index'))
        ->assertOk();

    // Create
    $response = $this->actingAs($superAdmin)
        ->post(route('brands.store'), [
            'name' => 'Brand Test Baru',
        ]);
    $response->assertRedirect(route('brands.index'));
    $this->assertDatabaseHas('brands', ['name' => 'Brand Test Baru']);

    $brand = Brand::where('name', 'Brand Test Baru')->first();

    // Update
    $response = $this->actingAs($superAdmin)
        ->put(route('brands.update', $brand->id), [
            'name' => 'Brand Test Edit',
        ]);
    $response->assertRedirect(route('brands.index'));
    $this->assertDatabaseHas('brands', ['name' => 'Brand Test Edit']);

    // Delete
    $response = $this->actingAs($superAdmin)
        ->delete(route('brands.destroy', $brand->id));
    $response->assertRedirect(route('brands.index'));
    $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
});

test('super admin can manage master categories', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    // Index
    $this->actingAs($superAdmin)
        ->get(route('categories.index'))
        ->assertOk();

    // Create
    $response = $this->actingAs($superAdmin)
        ->post(route('categories.store'), [
            'name' => 'Kategori Test Baru',
        ]);
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['name' => 'Kategori Test Baru']);

    $category = Category::where('name', 'Kategori Test Baru')->first();

    // Update
    $response = $this->actingAs($superAdmin)
        ->put(route('categories.update', $category->id), [
            'name' => 'Kategori Test Edit',
        ]);
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['name' => 'Kategori Test Edit']);

    // Delete
    $response = $this->actingAs($superAdmin)
        ->delete(route('categories.destroy', $category->id));
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('super admin can manage master subcategories', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $category = Category::create(['name' => 'Kategori Utama']);

    // Index
    $this->actingAs($superAdmin)
        ->get(route('sub-categories.index'))
        ->assertOk();

    // Create
    $response = $this->actingAs($superAdmin)
        ->post(route('sub-categories.store'), [
            'category_id' => $category->id,
            'name' => 'Sub Kategori Test Baru',
        ]);
    $response->assertRedirect(route('sub-categories.index'));
    $this->assertDatabaseHas('sub_categories', [
        'category_id' => $category->id,
        'name' => 'Sub Kategori Test Baru',
    ]);

    $subCategory = SubCategory::where('name', 'Sub Kategori Test Baru')->first();

    // Update
    $response = $this->actingAs($superAdmin)
        ->put(route('sub-categories.update', $subCategory->id), [
            'category_id' => $category->id,
            'name' => 'Sub Kategori Test Edit',
        ]);
    $response->assertRedirect(route('sub-categories.index'));
    $this->assertDatabaseHas('sub_categories', [
        'category_id' => $category->id,
        'name' => 'Sub Kategori Test Edit',
    ]);

    // Delete
    $response = $this->actingAs($superAdmin)
        ->delete(route('sub-categories.destroy', $subCategory->id));
    $response->assertRedirect(route('sub-categories.index'));
    $this->assertDatabaseMissing('sub_categories', ['id' => $subCategory->id]);
});

test('super admin can access unified master product list', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Master Produk (Katalog Terpadu)');
});
