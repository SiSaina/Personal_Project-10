<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**d
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Admin', 'Employee', 'Customer'] as $roleType) {
            Role::updateOrCreate(['role_type' => $roleType]);
        }
    }
}
