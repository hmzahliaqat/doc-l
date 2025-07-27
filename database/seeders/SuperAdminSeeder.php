<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super-admin user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'Emarkethosting@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Assign super-admin role to the user
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $user->assignRole($role);
        }
    }
}
