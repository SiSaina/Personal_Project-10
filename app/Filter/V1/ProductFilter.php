<?php

namespace App\Filter\V1;

use App\Filter\ApiFilter;

class ProductFilter extends ApiFilter
{
    protected $safeParms = [
        'name' => ['eq'],
        'categoryId' => ['eq'],
        'price' => ['eq', 'lt', 'lte', 'gt', 'gte'],
        'offerPrice' => ['eq', 'lt', 'lte', 'gt', 'gte'],
        'date' => ['eq', 'lt', 'lte', 'gt', 'gte'],
        'description' => ['eq'],
    ];

    protected $columnMap = [
        'categoryId' => 'category_id',
        'offerPrice' => 'offer_price',
    ];
}
