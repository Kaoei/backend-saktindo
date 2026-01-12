<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => "superadmin@example.com"],
            [
                'name' => "Super Admin",
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => Hash::make("password"),
            ]
        );
    }
}
