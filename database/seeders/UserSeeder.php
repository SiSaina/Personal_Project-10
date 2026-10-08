<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Local development accounts. The User model hashes these passwords.
        $accounts = [
            ['name' => 'dara', 'email' => 'dara@gmail.com', 'password' => 'dara1234', 'role' => 'Admin'],
            ['name' => 'lina', 'email' => 'lina@gmail.com', 'password' => 'lina1234', 'role' => 'Employee'],
            ['name' => 'rita', 'email' => 'rita@gmail.com', 'password' => 'rita1234', 'role' => 'Customer'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'],
                    'role_id' => Role::where('role_type', $account['role'])->firstOrFail()->id,
                ],
            );
        }
    }
}
