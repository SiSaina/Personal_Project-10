<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class RoleFilter extends ApiFilter
{
    protected $safeParms = [
        'name' => ['eq'],
    ];
}
