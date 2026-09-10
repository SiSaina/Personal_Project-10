<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'percent_off', 'active', 'expires_at'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_at' => 'datetime'];
    }
}
