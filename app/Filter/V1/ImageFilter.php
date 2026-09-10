<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class ImageFilter extends ApiFilter
{
    protected $safeParms = [
        'url' => ['eq'],
        'productId' => ['eq'],
    ];

    protected $columnMap = [
        'productId' => 'product_id',
    ];
}
