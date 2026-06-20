<?php

// Add this mapping to the module/app EventServiceProvider $listen array:

protected $listen = [
    \Modules\Flight\Events\SupplierPaymentConfirmed::class => [
        \Modules\Flight\Listeners\ProcessSupplierTicketing::class,
    ],
];
