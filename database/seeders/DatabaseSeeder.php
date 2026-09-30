<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeds the two local personas for manual testing.
     *
     * The Admin (admin@example.com) exercises the admin panel; the Member
     * (member@example.com) exercises the dashboard and settings as a
     * non-admin. SSO cannot run locally, so both carry the factory password
     * ("password") and sign in through the break-glass login at /backup/login.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
        ]);
    }
}
