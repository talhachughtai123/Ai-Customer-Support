<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed one synthetic account per role.
     *
     * SYNTHETIC DATA ONLY — never seed real customer PII (org policy).
     * All accounts share the local-dev password "password".
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Test Owner', 'email' => 'owner@example.com', 'role' => Role::Owner],
            ['name' => 'Test Administrator', 'email' => 'admin@example.com', 'role' => Role::Administrator],
            ['name' => 'Test Agent', 'email' => 'agent@example.com', 'role' => Role::SupportAgent],
            ['name' => 'Test Viewer', 'email' => 'viewer@example.com', 'role' => Role::Viewer],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$account['role']->value]);
        }
    }
}
