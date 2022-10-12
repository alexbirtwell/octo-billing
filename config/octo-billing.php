<?php

return [
    'currency' => env('CURRENCY','£'),
    'dont_prorate_on_swap' => true,
    'subscription_index' => env('SUBSCRIPTION_INDEX_ROUTE','/billing/subscription/')
];
