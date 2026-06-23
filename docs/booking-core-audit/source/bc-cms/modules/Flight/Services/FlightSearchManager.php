<?php

namespace Modules\Flight\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FlightSearchManager
{
    public function usesSupplierMode(): bool
    {
        return config('flight.provider_mode', 'database') !== 'database';
    }

    public function search(array $input): array
    {
        $criteria = $this->normalizeCriteria($input);
        $response = [];
        $source = 'live';
        $startedAt = microtime(true);

        /** @var FlightSearchGuard $guard */
        $guard = app(FlightSearchGuard::class);

        try {
            /** @var TsaSupplierBridgeClient $bridge */
            $bridge = app(TsaSupplierBridgeClient::class);

            [$response, $source] = $guard->rememberWithSource($criteria, function () use ($bridge, $criteria) {
                return $bridge->search($criteria);
            });
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'offers' => [],
                'error' => $e->getMessage(),
            ];
        }

        $rawOffers = $this->extractOffers($response);

        try {
            $guard->record($criteria, [
                'status' => empty($response['error']) ? 'allowed' : 'failed',
                'source' => $source,
                'supplier_code' => Arr::get($response, 'supplier_code') ?: Arr::get($response, 'data.supplier_code'),
                'offers_count' => count($rawOffers),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => Arr::get($response, 'error'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $offers = collect($rawOffers)
            ->map(fn (array $offer) => $this->transformSupplierOffer($offer, $criteria))
            ->filter(fn (?array $offer) => !empty($offer))
            ->values();

        $offers = $this->decorateOfferBadges($this->sortOffers($offers, $criteria['sort']));
        $selectedOffer = $offers->firstWhere('is_selected', true) ?: $offers->first();

        return [
            'criteria' => $criteria,
            'search_id' => Arr::get($response, 'search_id') ?: Arr::get($response, 'data.search_id'),
            'offers' => $offers,
            'selectedOffer' => $selectedOffer,
            'airlineFilters' => $this->buildAirlineFilters($offers),
            'supportCard' => [
                'title' => __('Canli destek hazir'),
                'body' => empty($rawOffers)
                    ? __('Su an uygun ucus sonucu bulunamadi. Supplier engine baglantisi ve rota kriterlerini kontrol edin.')
                    : __('Sorulariniz icin bu alan daha sonra operasyon ve destek akisina baglanacak.'),
            ],
            'raw' => $response,
        ];
    }

    protected function extractOffers(array $response): array
    {
        $offers = Arr::get($response, 'offers');
        if (is_array($offers)) {
            return $offers;
        }

        $offers = Arr::get($response, 'data.offers');
        if (is_array($offers)) {
            return $offers;
        }

        $offers = Arr::get($response, 'results');
        if (is_array($offers)) {
            return $offers;
        }

        return [];
    }

    protected function normalizeCriteria(array $input): array
    {
        $origin = $this->normalizeAirportCode($input['origin'] ?? $input['from_where'] ?? '');
        $destination = $this->normalizeAirportCode($input['destination'] ?? $input['to_where'] ?? '');
        $departureDate = $input['departure_date'] ?? $input['date'] ?? $input['start'] ?? now()->format('Y-m-d');
        $returnDate = $input['return_date'] ?? $input['end'] ?? null;
        $adultCount = max(1, (int) ($input['adult_count'] ?? $input['adults'] ?? 1));
        $childCount = max(0, (int) ($input['child_count'] ?? $input['children'] ?? 0));
        $infantCount = max(0, (int) ($input['infant_count'] ?? $input['infants'] ?? 0));
        $airlines = array_values(array_filter((array) ($input['airlines'] ?? $input['airline'] ?? [])));

        return [
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'adult_count' => $adultCount,
            'child_count' => $childCount,
            'infant_count' => $infantCount,
            'passenger_total' => $adultCount + $childCount + $infantCount,
            'cabin_class' => $input['cabin_class'] ?? $input['seat_type'] ?? 'economy',
            'sort' => $input['sort'] ?? 'recommended',
            'direct_only' => $this->normalizeBoolean($input['direct_only'] ?? $input['only_direct'] ?? false),
            'airlines' => $airlines,
            'selected_offer' => $input['selected_offer'] ?? null,
            'selected_fare' => $input['selected_fare'] ?? null,
        ];
    }

    protected function normalizeAirportCode(?string $value): ?string
    {
        $cleanValue = strtoupper(trim((string) $value));
        if (!$cleanValue) {
            return null;
        }

        if (preg_match('/[A-Z]{3}/', $cleanValue, $matches)) {
            return $matches[0];
        }

        return $cleanValue;
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    protected function transformSupplierOffer(array $offer, array $criteria): array
    {
        $offerId = (string) (Arr::get($offer, 'offer_id') ?: Arr::get($offer, 'id') ?: Arr::get($offer, 'template_id') ?: Str::uuid());
        $supplierCode = (string) (Arr::get($offer, 'supplier') ?: Arr::get($offer, 'provider') ?: Arr::get($offer, 'supplier_context.supplier_code') ?: 'supplier');

        $origin = $this->airportCode(Arr::get($offer, 'origin')) ?: $this->airportCode(Arr::get($offer, 'origin.code')) ?: $criteria['origin'];
        $destination = $this->airportCode(Arr::get($offer, 'destination')) ?: $this->airportCode(Arr::get($offer, 'destination.code')) ?: $criteria['destination'];

        $departureAt = $this->parseDateTime(
            Arr::get($offer, 'departure_at') ?: Arr::get($offer, 'departure.datetime') ?: Arr::get($offer, 'segments.0.departure_at'),
            $criteria['departure_date'],
            Arr::get($offer, 'base_departure_time', '00:00')
        );

        $arrivalAt = $this->parseDateTime(
            Arr::get($offer, 'arrival_at') ?: Arr::get($offer, 'arrival.datetime') ?: Arr::get($offer, 'segments.0.arrival_at'),
            $criteria['departure_date'],
            Arr::get($offer, 'base_arrival_time')
        );

        $durationMinutes = (int) (Arr::get($offer, 'duration_minutes') ?: $departureAt->diffInMinutes($arrivalAt, false));
        if ($durationMinutes <= 0) {
            $durationMinutes = 60;
            $arrivalAt = (clone $departureAt)->addMinutes($durationMinutes);
        }

        $currency = $this->extractCurrency($offer);
        $basePrice = $this->extractAmount($offer);
        $fareOptions = $this->buildFareOptions($offer, $criteria, $offerId, $basePrice, $currency);
        $selectedFare = $fareOptions->firstWhere('is_selected', true)
            ?: $fareOptions->sortBy('total_price')->first();

        $airlineCode = (string) (Arr::get($offer, 'airline.code') ?: Arr::get($offer, 'airline_code') ?: Arr::get($offer, 'marketing_airline.code') ?: '--');
        $airlineName = (string) (Arr::get($offer, 'airline.name') ?: Arr::get($offer, 'airline_name') ?: Arr::get($offer, 'marketing_airline.name') ?: $airlineCode ?: __('Airline'));
        $stopCount = (int) (Arr::get($offer, 'stop_count') ?? max(0, count((array) Arr::get($offer, 'segments', [])) - 1));
        $isSelectedOffer = ($criteria['selected_offer'] ?? null) === $offerId;

        $payload = $offer;
        $payload['offer_id'] = Arr::get($payload, 'offer_id') ?: $offerId;
        $payload['id'] = Arr::get($payload, 'id') ?: $offerId;
        $payload['supplier'] = Arr::get($payload, 'supplier') ?: $supplierCode;
        $payload['supplier_context'] = Arr::get($payload, 'supplier_context', [
            'supplier_code' => $supplierCode,
            'raw_offer_id' => Arr::get($offer, 'supplier_offer_id') ?: $offerId,
            'expires_at' => Arr::get($offer, 'expires_at'),
        ]);

        return [
            'id' => $offerId,
            'offer_id' => $offerId,
            'template_id' => $offerId,
            'supplier_offer_id' => Arr::get($offer, 'supplier_offer_id') ?: Arr::get($offer, 'raw_offer_id') ?: $offerId,
            'provider' => $supplierCode,
            'supplier' => $supplierCode,
            'supplier_context' => Arr::get($payload, 'supplier_context'),
            'payload' => $payload,
            'airline_name' => $airlineName,
            'airline_code' => $airlineCode,
            'airline_initials' => Str::upper(substr($airlineCode ?: $airlineName, 0, 2)),
            'origin' => $origin,
            'destination' => $destination,
            'route_label' => ($origin ?: '---') . ' → ' . ($destination ?: '---'),
            'departure_at' => $departureAt,
            'arrival_at' => $arrivalAt,
            'departure_time_label' => $departureAt->format('H:i'),
            'arrival_time_label' => $arrivalAt->format('H:i'),
            'departure_date_label' => $departureAt->translatedFormat('d M D'),
            'arrival_date_label' => $arrivalAt->translatedFormat('d M D'),
            'duration_minutes' => $durationMinutes,
            'duration_label' => $this->durationLabel($durationMinutes),
            'stop_count' => $stopCount,
            'stop_label' => $stopCount === 0 ? __('Direkt Ucus') : __(':count aktarma', ['count' => $stopCount]),
            'base_price' => $basePrice,
            'base_price_label' => $this->money($basePrice, $currency),
            'price_currency' => $currency,
            'currency' => $currency,
            'fare_family' => Arr::get($offer, 'fare_family') ?: Arr::get($selectedFare, 'label') ?: __('Standart'),
            'baggage_summary' => Arr::get($offer, 'baggage_summary') ?: Arr::get($offer, 'baggage.checked') ?: null,
            'cancellation_policy' => Arr::get($offer, 'rules.cancellation') ?: Arr::get($offer, 'cancellation_policy'),
            'seat_pitch' => Arr::get($offer, 'seat_pitch'),
            'package_score' => (int) (Arr::get($offer, 'package_score') ?: 0),
            'fare_options' => $fareOptions,
            'selected_fare' => $selectedFare,
            'display_price' => $selectedFare['total_price_label'] ?? $this->money($basePrice, $currency),
            'display_features' => array_slice($selectedFare['features'] ?? [], 0, 3),
            'display_checked_baggage' => ($selectedFare['checked_baggage'] ?? null) ?: Arr::get($offer, 'baggage_summary') ?: Arr::get($offer, 'baggage.checked'),
            'display_hand_baggage' => $selectedFare['hand_baggage'] ?? Arr::get($offer, 'baggage.hand'),
            'display_meal' => (bool) ($selectedFare['meal_included'] ?? Arr::get($offer, 'capabilities.meal_selection_supported', false)),
            'display_seat_selection' => (bool) ($selectedFare['seat_selection'] ?? Arr::get($offer, 'capabilities.seat_selection_supported', false)),
            'display_refundable' => (bool) ($selectedFare['refundable'] ?? Arr::get($offer, 'capabilities.refundable', false)),
            'display_exchangeable' => (bool) ($selectedFare['exchangeable'] ?? Arr::get($offer, 'capabilities.exchangeable', false)),
            'is_selected' => $isSelectedOffer,
            'badges' => [],
            'expires_at' => Arr::get($offer, 'expires_at'),
            'rules' => Arr::get($offer, 'rules', []),
            'capabilities' => Arr::get($offer, 'capabilities', []),
        ];
    }

    protected function buildFareOptions(array $offer, array $criteria, string $offerId, float $basePrice, string $currency): Collection
    {
        $rawFares = Arr::get($offer, 'fare_options');
        if (!is_array($rawFares) || empty($rawFares)) {
            $rawFares = [[
                'id' => Arr::get($offer, 'fare_id', 'standard'),
                'label' => Arr::get($offer, 'fare_family', __('Standart')),
                'total_price' => $basePrice,
                'currency' => $currency,
                'features' => Arr::get($offer, 'features', []),
            ]];
        }

        return collect($rawFares)->map(function (array $fare) use ($criteria, $offerId, $basePrice, $currency) {
            $fareId = (string) (Arr::get($fare, 'id') ?: Arr::get($fare, 'fare_id') ?: 'standard');
            $fareCurrency = (string) (Arr::get($fare, 'currency') ?: $currency);
            $totalPrice = (float) (
                Arr::get($fare, 'total_price')
                ?? Arr::get($fare, 'price.amount')
                ?? ((float) $basePrice + (float) Arr::get($fare, 'price_delta', 0))
            );
            $priceDelta = (float) Arr::get($fare, 'price_delta', $totalPrice - $basePrice);

            return [
                'id' => $fareId,
                'fare_id' => $fareId,
                'label' => Arr::get($fare, 'label') ?: Arr::get($fare, 'name') ?: __('Paket'),
                'badge' => Arr::get($fare, 'badge'),
                'price_delta' => $priceDelta,
                'total_price' => $totalPrice,
                'total_amount' => $totalPrice,
                'currency' => $fareCurrency,
                'total_price_label' => $this->money($totalPrice, $fareCurrency),
                'delta_label' => ($priceDelta > 0 ? '+' : '') . $this->money($priceDelta, $fareCurrency),
                'hand_baggage' => Arr::get($fare, 'hand_baggage') ?: Arr::get($fare, 'baggage.hand'),
                'checked_baggage' => Arr::get($fare, 'checked_baggage') ?: Arr::get($fare, 'baggage.checked'),
                'features' => array_values((array) Arr::get($fare, 'features', [])),
                'seat_selection' => (bool) Arr::get($fare, 'seat_selection', false),
                'meal_included' => (bool) Arr::get($fare, 'meal_included', false),
                'refundable' => (bool) Arr::get($fare, 'refundable', false),
                'exchangeable' => (bool) Arr::get($fare, 'exchangeable', false),
                'is_selected' => ($criteria['selected_offer'] ?? null) === $offerId
                    && ($criteria['selected_fare'] ?? null) === $fareId,
            ];
        })->values();
    }

    protected function airportCode(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['code'] ?? $value['iata'] ?? null;
        }

        return $this->normalizeAirportCode($value);
    }

    protected function parseDateTime(?string $value, ?string $fallbackDate, ?string $fallbackTime = '00:00'): Carbon
    {
        if ($value) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                // Continue to fallback below.
            }
        }

        return Carbon::parse(($fallbackDate ?: now()->format('Y-m-d')) . ' ' . ($fallbackTime ?: '00:00'));
    }

    protected function extractAmount(array $offer): float
    {
        return (float) (
            Arr::get($offer, 'price.amount')
            ?? Arr::get($offer, 'total_amount')
            ?? Arr::get($offer, 'base_price')
            ?? Arr::get($offer, 'amount')
            ?? 0
        );
    }

    protected function extractCurrency(array $offer): string
    {
        return (string) (
            Arr::get($offer, 'price.currency')
            ?? Arr::get($offer, 'currency')
            ?? Arr::get($offer, 'price_currency')
            ?? 'EUR'
        );
    }

    protected function sortOffers(Collection $offers, string $sort): Collection
    {
        return match ($sort) {
            'price' => $offers->sortBy(fn (array $offer) => $offer['selected_fare']['total_price'] ?? 0)->values(),
            'duration' => $offers->sortBy('duration_minutes')->values(),
            'departure' => $offers->sortBy(fn (array $offer) => $offer['departure_at']->timestamp)->values(),
            default => $offers->sortByDesc(function (array $offer) {
                return (($offer['package_score'] ?? 0) * 1000) - ($offer['selected_fare']['total_price'] ?? 0);
            })->values(),
        };
    }

    protected function decorateOfferBadges(Collection $offers): Collection
    {
        if ($offers->isEmpty()) {
            return $offers;
        }

        $minPrice = $offers->min(fn (array $offer) => $offer['selected_fare']['total_price'] ?? 0);
        $minDuration = $offers->min('duration_minutes');

        return $offers->map(function (array $offer) use ($minPrice, $minDuration) {
            $badges = [];
            if (($offer['selected_fare']['total_price'] ?? 0) === $minPrice) {
                $badges[] = __('En ucuz');
            }
            if (($offer['duration_minutes'] ?? 0) === $minDuration) {
                $badges[] = __('En hizli');
            }
            if (($offer['stop_count'] ?? 0) === 0) {
                $badges[] = __('Direkt');
            }
            $offer['badges'] = $badges;

            return $offer;
        })->values();
    }

    protected function buildAirlineFilters(Collection $offers): array
    {
        return $offers
            ->groupBy('airline_code')
            ->map(function (Collection $group, string $code) {
                $first = $group->first();

                return [
                    'code' => $code,
                    'name' => $first['airline_name'] ?? $code,
                    'count' => $group->count(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    protected function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return sprintf('%dsa %02ddk', $hours, $mins);
        }
        if ($hours > 0) {
            return sprintf('%dsa', $hours);
        }

        return sprintf('%ddk', $mins);
    }

    protected function money(float $amount, string $currency): string
    {
        $symbol = match (strtoupper($currency)) {
            'TRY' => '₺',
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        return $symbol . number_format($amount, 0, ',', '.');
    }
}
