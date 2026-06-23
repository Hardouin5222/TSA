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

    // TSA Search Guard / Look-to-Book protection
    'search_guard_enabled' => env('TSA_SEARCH_GUARD_ENABLED', true),
    'search_cache_ttl_seconds' => env('TSA_SEARCH_CACHE_TTL_SECONDS', 900),
    'search_rate_limit_per_minute' => env('TSA_SEARCH_RATE_LIMIT_PER_MINUTE', 12),
    'search_rate_limit_per_hour' => env('TSA_SEARCH_RATE_LIMIT_PER_HOUR', 120),
    'search_l2b_enabled' => env('TSA_SEARCH_L2B_ENABLED', true),
    'search_l2b_hard_block' => env('TSA_SEARCH_L2B_HARD_BLOCK', false),
    'search_l2b_warning_ratio' => env('TSA_SEARCH_L2B_WARNING_RATIO', 1200),
    'search_l2b_block_ratio' => env('TSA_SEARCH_L2B_BLOCK_RATIO', 1450),
    'search_l2b_window_days' => env('TSA_SEARCH_L2B_WINDOW_DAYS', 30),
    'search_live_supplier_modes' => array_values(array_filter(array_map('trim', explode(',', env('TSA_SEARCH_LIVE_SUPPLIER_MODES', 'duffel_sandbox,biletbank_sandbox'))))),
];
