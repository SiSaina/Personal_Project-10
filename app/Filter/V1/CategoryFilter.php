<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class CategoryFilter extends ApiFilter
{
    protected $safeParms = [
        'name' => ['eq'],
    ];
}
