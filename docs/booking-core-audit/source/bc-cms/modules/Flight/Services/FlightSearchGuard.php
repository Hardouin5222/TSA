<?php

namespace Modules\Flight\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Flight\Models\SupplierSearchLog;
use Modules\Flight\Models\SupplierBooking;

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
        $sessionId = null;

        try {
            if (method_exists($request, 'hasSession') && $request->hasSession()) {
                $sessionId = $request->session()->getId();
            }
        } catch (\Throwable $e) {
            $sessionId = null;
        }

        return [
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'session_hash' => $sessionId ? hash('sha256', (string) $sessionId) : null,
            'user_id' => Auth::id(),
            'search_hash' => $this->fingerprint($criteria),
            'cache_key' => $this->cacheKey($criteria),
        ];
    }

    public function assertAllowed(array $criteria): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->assertLookToBookAllowed();

        $context = $this->currentContext($criteria);
        $actorKey = $this->rateLimitActorKey($context);

        $minuteLimit = max(1, (int) config('flight.search_rate_limit_per_minute', 12));
        $hourLimit = max($minuteLimit, (int) config('flight.search_rate_limit_per_hour', 120));

        $minuteCount = $this->incrementRateCounter('tsa:flight-search-rate:minute:' . $actorKey, 60);
        if ($minuteCount > $minuteLimit) {
            throw new \RuntimeException('SEARCH_RATE_LIMITED: too many live flight searches per minute');
        }

        $hourCount = $this->incrementRateCounter('tsa:flight-search-rate:hour:' . $actorKey, 3600);
        if ($hourCount > $hourLimit) {
            throw new \RuntimeException('SEARCH_RATE_LIMITED: too many live flight searches per hour');
        }
    }

    public function assertLookToBookAllowed(): void
    {
        if (!$this->shouldEnforceLookToBook()) {
            return;
        }

        $snapshot = $this->lookToBookSnapshot();
        $blockRatio = (float) config('flight.search_l2b_block_ratio', 1450);

        $isOverLimit = $snapshot['search_count'] > $blockRatio && $snapshot['ratio'] > $blockRatio;

        if (!$isOverLimit) {
            return;
        }

        if (!(bool) config('flight.search_l2b_hard_block', false)) {
            return;
        }

        throw new \RuntimeException('SEARCH_L2B_BLOCKED: live flight search paused because look-to-book ratio is too high');
    }

    public function lookToBookSnapshot(): array
    {
        $windowDays = max(1, (int) config('flight.search_l2b_window_days', 30));
        $since = now()->subDays($windowDays);

        $searchCount = SupplierSearchLog::query()
            ->where('created_at', '>=', $since)
            ->where('status', 'allowed')
            ->whereIn('source', ['live', 'live_unprotected'])
            ->count();

        $bookingCount = SupplierBooking::query()
            ->where('created_at', '>=', $since)
            ->whereIn('fulfillment_status', ['ticketing_in_progress', 'booking_confirmed', 'ticket_issued'])
            ->count();

        $denominator = max(1, $bookingCount);
        $ratio = $searchCount / $denominator;

        return [
            'enabled' => $this->shouldEnforceLookToBook(),
            'window_days' => $windowDays,
            'search_count' => $searchCount,
            'booking_count' => $bookingCount,
            'ratio' => round($ratio, 4),
            'warning_ratio' => (float) config('flight.search_l2b_warning_ratio', 1200),
            'block_ratio' => (float) config('flight.search_l2b_block_ratio', 1450),
            'hard_block' => (bool) config('flight.search_l2b_hard_block', false),
            'supplier_mode' => strtolower((string) config('flight.supplier_engine_mode', 'mock')),
        ];
    }

    protected function shouldEnforceLookToBook(): bool
    {
        if (!(bool) config('flight.search_l2b_enabled', true)) {
            return false;
        }

        $mode = strtolower((string) config('flight.supplier_engine_mode', 'mock'));

        if (in_array($mode, ['mock', 'mock_supplier', 'mock_supplier_engine'], true)) {
            return false;
        }

        $liveModes = array_map('strtolower', (array) config('flight.search_live_supplier_modes', []));

        return $mode === 'bridge' || empty($liveModes) || in_array($mode, $liveModes, true);
    }

    protected function rateLimitActorKey(array $context): string
    {
        if (!empty($context['user_id'])) {
            return 'user:' . $context['user_id'];
        }

        if (!empty($context['session_hash'])) {
            return 'session:' . $context['session_hash'];
        }

        return 'ip:' . ($context['ip_hash'] ?? 'unknown');
    }

    protected function incrementRateCounter(string $key, int $seconds): int
    {
        Cache::add($key, 0, $seconds);

        return (int) Cache::increment($key);
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

    public function rememberWithSource(array $criteria, callable $callback): array
    {
        if (!$this->isEnabled()) {
            return [$callback(), 'live_unprotected', false];
        }

        $cacheKey = $this->cacheKey($criteria);

        if (Cache::has($cacheKey)) {
            return [Cache::get($cacheKey), 'cache', true];
        }

        $this->assertAllowed($criteria);

        $response = $callback();
        Cache::put($cacheKey, $response, $this->cacheTtl());

        return [$response, 'live', false];
    }

    public function remember(array $criteria, callable $callback): array
    {
        return $this->rememberWithSource($criteria, $callback)[0];
    }
}
