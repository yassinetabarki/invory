<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Display Currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code used by the frontend to format money. Amounts are stored
    | and transported as decimal strings; this value only affects display.
    | Defaults to the Tunisian dinar (TND); override with APP_CURRENCY.
    |
    */

    'currency' => env('APP_CURRENCY', 'TND'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default number of rows per page on index screens.
    |
    */

    'per_page' => 25,

];
