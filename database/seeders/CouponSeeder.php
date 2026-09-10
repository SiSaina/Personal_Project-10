<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(['code' => 'WELCOME10'], ['percent_off' => 10, 'active' => true]);
    }
}
