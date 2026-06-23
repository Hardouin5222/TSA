<?php

namespace Modules\Flight\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Flight\Models\SupplierSearchLog;

class FlightSearchGuard
{
    public function fingerprint(array $criteria): string
    {
        $normalized = [
            'origin' => strtoupper((string) Arr::get($criteria, 'origin')),
            'destination' => strtoupper((string) Arr::get($criteria, 'destination')),
            'departure_date' => (string) Arr::get($criteria, 'departure_date'),
            'return_date' => (string) Arr::get($criteria, 'return_date'),
            'adult_count' => (int) Arr::get($criteria, 'adult_count', 1),
            'child_count' => (int) Arr::get($criteria, 'child_count', 0),
            'infant_count' => (int) Arr::get($criteria, 'infant_count', 0),
            'cabin_class' => strtolower((string) Arr::get($criteria, 'cabin_class', 'economy')),
            'direct_only' => (bool) Arr::get($criteria, 'direct_only', false),
        ];

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES));
    }

    public function cacheKey(array $criteria): string
    {
        return 'tsa:flight-search:' . $this->fingerprint($criteria);
    }

    public function cacheTtl(): int
    {
        return max(60, (int) config('flight.search_cache_ttl_seconds', 900));
    }

    public function isEnabled(): bool
    {
        return (bool) config('flight.search_guard_enabled', true);
    }

    public function currentContext(array $criteria): array
    {
        $request = request();

        return [
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'session_hash' => hash('sha256', (string) optional($request->session())->getId()),
            'user_id' => Auth::id(),
            'search_hash' => $this->fingerprint($criteria),
            'cache_key' => $this->cacheKey($criteria),
        ];
    }

    public function record(array $criteria, array $meta = []): SupplierSearchLog
    {
        $context = $this->currentContext($criteria);

        return SupplierSearchLog::create([
            'search_uuid' => (string) Str::uuid(),
            'search_hash' => $context['search_hash'],
            'supplier_mode' => (string) config('flight.supplier_engine_mode', 'mock'),
            'supplier_code' => Arr::get($meta, 'supplier_code'),
            'origin' => Arr::get($criteria, 'origin'),
            'destination' => Arr::get($criteria, 'destination'),
            'departure_date' => Arr::get($criteria, 'departure_date'),
            'return_date' => Arr::get($criteria, 'return_date'),
            'adult_count' => (int) Arr::get($criteria, 'adult_count', 1),
            'child_count' => (int) Arr::get($criteria, 'child_count', 0),
            'infant_count' => (int) Arr::get($criteria, 'infant_count', 0),
            'cabin_class' => Arr::get($criteria, 'cabin_class'),
            'user_id' => $context['user_id'],
            'ip_hash' => $context['ip_hash'],
            'session_hash' => $context['session_hash'],
            'status' => Arr::get($meta, 'status', 'allowed'),
            'source' => Arr::get($meta, 'source'),
            'offers_count' => (int) Arr::get($meta, 'offers_count', 0),
            'duration_ms' => Arr::get($meta, 'duration_ms'),
            'criteria_json' => $criteria,
            'guard_context_json' => [
                'cache_key' => $context['cache_key'],
                'l2b_warning_ratio' => config('flight.search_l2b_warning_ratio'),
                'l2b_block_ratio' => config('flight.search_l2b_block_ratio'),
            ],
            'error_message' => Arr::get($meta, 'error_message'),
        ]);
    }

    public function remember(array $criteria, callable $callback): array
    {
        if (!$this->isEnabled()) {
            return $callback();
        }

        return Cache::remember($this->cacheKey($criteria), $this->cacheTtl(), $callback);
    }
}
