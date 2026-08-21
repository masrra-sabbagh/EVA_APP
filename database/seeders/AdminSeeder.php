<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $admin = User::firstOrCreate(
            ['phone_number' => '0957575757'],
            [
                'user_name'     => 'Super Admin',
                'profile_image'  => 'profiles/superadmin.jpg',
                'work_or_study' => 'Administrator',
                'password'      => Hash::make('57575757'),
                'is_verified'   => true,
            ]
        );

        $adminRoleId = Role
            ::where('role_type', 'admin')->value('id');

        if (!$admin->roles()->where('role_id', $adminRoleId)->exists()) {
            $admin->roles()->attach($adminRoleId);
        }
    }
}
