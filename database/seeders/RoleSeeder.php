<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Admin', 'Employee', 'Customer'] as $roleType) {
            Role::firstOrCreate(['role_type' => $roleType]);
        }
    }
}
