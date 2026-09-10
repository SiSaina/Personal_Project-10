<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'postal_code',
        'street_name',
        'suburb',
        'city',
        'country',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
