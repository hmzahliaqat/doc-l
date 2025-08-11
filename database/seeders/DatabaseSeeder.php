<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the role seeder first to ensure roles exist
        $this->call(RoleSeeder::class);

        // Then call the super admin seeder to create the super admin user
        $this->call(SuperAdminSeeder::class);

        // Create the default privacy policy
        $this->call(PrivacyPolicySeeder::class);

        // Optionally create test users
        // User::factory(10)->create();
    }
}
