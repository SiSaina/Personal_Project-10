<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class UserFilter extends ApiFilter
{
    protected $safeParms = [
        'name' => ['eq'],
        'email' => ['eq'],
        'phone' => ['eq'],
        'imageUrl' => ['eq'],
        'roleId' => ['eq'],
    ];

    protected $columnMap = [
        'imageUrl' => 'image_url',
        'roleId' => 'role_id',
    ];
}
