<?php

return [

    /*
    |--------------------------------------------------------------------------
    | New-order notification
    |--------------------------------------------------------------------------
    |
    | Who gets told when an order is paid, alongside the customer's own
    | confirmation. Comma-separate for several recipients. Leave empty to
    | switch the notification off.
    |
    */

    'notify_email' => env('ORDER_NOTIFY_EMAIL', env('ADMIN_EMAIL')),

];
