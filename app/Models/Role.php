<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'role_type',
    ];

    protected function roleType(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => match (strtolower(trim($value ?? ''))) {
                'admin' => 'Admin',
                'employee' => 'Employee',
                'customer' => 'Customer',
                default => $value,
            },
        );
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
