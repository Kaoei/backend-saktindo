<?php

use App\Models\User;

test('super admin can access create user page', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $this->actingAs($superAdmin)
        ->get('/users/create')
        ->assertOk();
});

test('non super admin is forbidden to access create user page', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->get('/users/create')
        ->assertForbidden();
});
