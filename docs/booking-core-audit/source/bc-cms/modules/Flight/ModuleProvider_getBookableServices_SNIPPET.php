<?php

// Do NOT edit AppHelper.php for this mapping.
// AppHelper::get_bookable_services() already collects mappings from each active module's ModuleProvider::getBookableServices().
// Add the tsa_supplier_flight entry inside modules/Flight/ModuleProvider.php.

public static function getBookableServices()
{
    return [
        'flight' => \Modules\Flight\Models\Flight::class,
        'tsa_supplier_flight' => \Modules\Flight\Models\SupplierOffer::class,
    ];
}
