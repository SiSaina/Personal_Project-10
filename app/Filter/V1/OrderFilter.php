<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class OrderFilter extends ApiFilter
{
    protected $safeParms = [
        'userId' => ['eq'],
        'status' => ['eq'],
        'placedAt' => ['eq', 'lt', 'lte', 'gt', 'gte'],
    ];

    protected $columnMap = [
        'userId' => 'user_id',
        'placedAt' => 'placed_at',
    ];
}
