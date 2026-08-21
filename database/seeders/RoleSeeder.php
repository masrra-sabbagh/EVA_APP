<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    public function run(): void {
        $roles = ['user', 'provider', 'admin'];

        foreach ($roles as $roleType) {
            Role::firstOrCreate(['role_type' => $roleType]);
        }
    }
}
