<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the four application roles (idempotent).
     */
    public function run(): void
    {
        foreach (RoleEnum::values() as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
