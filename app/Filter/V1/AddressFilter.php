<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class AddressFilter extends ApiFilter
{
    protected $safeParms = [
        'fullName' => ['eq'],
        'area' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'postalCode' => ['eq', 'gt', 'lt', 'gte', 'lte'],
        'userId' => ['eq'],
    ];

    protected $columnMap = [
        'fullName' => 'full_name',
        'postalCode' => 'postal_code',
        'userId' => 'user_id',
    ];
}
