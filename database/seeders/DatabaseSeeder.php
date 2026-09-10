<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            RoleSeeder::class,
            ProductSeeder::class,
            ImageSeeder::class,
            UserSeeder::class,
            AddressSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
