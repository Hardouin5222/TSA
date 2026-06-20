<?php
return [
    'flight_route_prefix' => env('FLIGHT_ROUTE_PREFIX', 'flight'),
    'provider_mode' => env('FLIGHT_PROVIDER_MODE', 'mock_supplier'),
    'mock_catalog_path' => env(
        'FLIGHT_MOCK_CATALOG_PATH',
        base_path('modules/Flight/MockData/flight_supplier_catalog.json')
    ),

    // TSA Supplier Bridge / API Engine
    'supplier_engine_base_url' => env('TSA_SUPPLIER_ENGINE_BASE_URL', 'http://tsa-supplier-engine:8010'),
    'supplier_engine_mode' => env('TSA_SUPPLIER_ENGINE_MODE', env('FLIGHT_PROVIDER_MODE', 'mock')),
    'supplier_engine_timeout' => env('TSA_SUPPLIER_ENGINE_TIMEOUT', 20),

    // Live mode must set this to false and implement provider signature checks in SupplierWebhookController.
    'disable_webhook_signature_check' => env('TSA_DISABLE_WEBHOOK_SIGNATURE_CHECK', env('APP_ENV') !== 'production'),
];
