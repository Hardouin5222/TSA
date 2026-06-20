<?php

namespace Modules\Flight\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TsaSupplierBridgeClient
{
    protected string $baseUrl;
    protected string $mode;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('flight.supplier_engine_base_url', env('TSA_SUPPLIER_ENGINE_BASE_URL', 'http://tsa-supplier-engine:8010')), '/');
        $this->mode = config('flight.supplier_engine_mode', env('TSA_SUPPLIER_ENGINE_MODE', 'mock'));
        $this->timeout = (int) config('flight.supplier_engine_timeout', env('TSA_SUPPLIER_ENGINE_TIMEOUT', 20));
    }

    public function search(array $criteria): array
    {
        if ($this->isMock()) {
            return $this->mockSearch($criteria);
        }

        return $this->post('/api/flights/search', $criteria);
    }

    public function quote(array $payload): array
    {
        if ($this->isMock()) {
            return $this->mockQuote($payload);
        }

        return $this->post('/api/flights/quote', $payload);
    }

    public function book(array $payload): array
    {
        if ($this->isMock()) {
            return $this->mockBook($payload);
        }

        return $this->post('/api/flights/book', $payload);
    }

    public function getStatus(string $reference): array
    {
        if ($this->isMock()) {
            return [
                'booking_status' => 'ticket_issued',
                'fulfillment_status' => 'ticket_issued',
                'supplier_booking_reference' => $reference,
            ];
        }

        return $this->get('/api/flights/bookings/'.$reference.'/status');
    }

    protected function post(string $path, array $payload): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->post($this->baseUrl.$path, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Supplier engine request failed: '.$response->status().' '.$response->body());
        }

        return $response->json() ?: [];
    }

    protected function get(string $path): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->get($this->baseUrl.$path);

        if (!$response->successful()) {
            throw new \RuntimeException('Supplier engine request failed: '.$response->status().' '.$response->body());
        }

        return $response->json() ?: [];
    }

    protected function headers(): array
    {
        return [
            'X-Correlation-Id' => request()->headers->get('X-Correlation-Id', (string) Str::uuid()),
            'X-Client' => 'booking-core-flight-bridge',
        ];
    }

    protected function isMock(): bool
    {
        return $this->mode === 'mock' || $this->mode === 'mock_supplier';
    }

    protected function mockSearch(array $criteria): array
    {
        $origin = strtoupper((string) (Arr::get($criteria, 'origin') ?: Arr::get($criteria, 'from_where') ?: 'IST'));
        $destination = strtoupper((string) (Arr::get($criteria, 'destination') ?: Arr::get($criteria, 'to_where') ?: 'LHR'));
        $departureDate = Arr::get($criteria, 'departure_date')
            ?: Arr::get($criteria, 'start_date')
            ?: Arr::get($criteria, 'start')
            ?: now()->addDays(14)->toDateString();

        try {
            $departure = \Carbon\Carbon::parse($departureDate)->setTime(10, 25);
        } catch (\Throwable $e) {
            $departure = now()->addDays(14)->setTime(10, 25);
        }

        $resolver = app(\Modules\Flight\Services\TsaFlightSupplierResolver::class);
        $routing = $resolver->resolveForQuote([
            'provider' => 'AMADEUS',
            'origin' => ['code' => $origin],
            'destination' => ['code' => $destination],
            'supplier_context' => ['supplier_code' => 'AMADEUS'],
        ], [
            'origin' => $origin,
            'destination' => $destination,
        ]);

        $currency = ($routing['market'] ?? 'TR') === 'TR' ? 'TRY' : 'USD';
        $basePrice = ($routing['market'] ?? 'TR') === 'TR' ? 1180.00 : 233.90;

        $makeOffer = function (int $index, string $airline, string $flightNumber, int $minutes, float $amount) use ($origin, $destination, $departure, $currency, $routing) {
            $departAt = $departure->copy()->addHours($index * 2);
            $arriveAt = $departAt->copy()->addMinutes($minutes);

            return [
                'id' => 'mock_'.$origin.'_'.$destination.'_'.$index,
                'offer_id' => 'mock_'.$origin.'_'.$destination.'_'.$index,
                'provider' => 'AMADEUS',
                'supplier' => 'AMADEUS',
                'supplier_code' => $routing['search_supplier'] ?? 'AMADEUS',
                'display_name' => $airline.' '.$flightNumber,
                'airline' => $airline,
                'flight_number' => $flightNumber,
                'origin' => [
                    'code' => $origin,
                    'name' => $origin,
                ],
                'destination' => [
                    'code' => $destination,
                    'name' => $destination,
                ],
                'departure_at' => $departAt->toIso8601String(),
                'arrival_at' => $arriveAt->toIso8601String(),
                'duration_minutes' => $minutes,
                'stop_count' => 0,
                'currency' => $currency,
                'price' => [
                    'amount' => $amount,
                    'currency' => $currency,
                ],
                'total_amount' => $amount,
                'fare_options' => [
                    [
                        'id' => 'eco',
                        'name' => 'Economy',
                        'price' => $amount,
                        'total_amount' => $amount,
                        'currency' => $currency,
                        'baggage' => 'Cabin baggage',
                    ],
                    [
                        'id' => 'flex',
                        'name' => 'Economy Flex',
                        'price' => $amount + 320,
                        'total_amount' => $amount + 320,
                        'currency' => $currency,
                        'baggage' => 'Cabin + checked baggage',
                    ],
                ],
                'baggage' => [
                    'cabin' => true,
                    'checked' => false,
                ],
                'rules' => [
                    'refundable' => false,
                    'exchangeable' => true,
                ],
                'capabilities' => [
                    'branded_fares_supported' => true,
                    'checked_baggage_supported' => true,
                    'seat_selection_supported' => false,
                    'hold_supported' => false,
                    'instant_ticketing_supported' => true,
                ],
                'supplier_context' => array_merge($routing, [
                    'supplier_code' => $routing['search_supplier'] ?? 'AMADEUS',
                    'search_supplier' => $routing['search_supplier'] ?? 'AMADEUS',
                    'ticketing_supplier' => $routing['ticketing_supplier'] ?? 'BILETBANK',
                    'payment_provider' => $routing['payment_provider'] ?? 'BILETBANK_POS',
                    'raw_offer_id' => 'mock_'.$origin.'_'.$destination.'_'.$index,
                    'pricing_token' => 'mock_pricing_'.$origin.'_'.$destination.'_'.$index,
                    'expires_at' => now()->addMinutes(15)->toIso8601String(),
                ]),
            ];
        };

        return [
            'search_id' => 'mock_search_'.Str::uuid(),
            'supplier_code' => $routing['search_supplier'] ?? 'AMADEUS',
            'market' => $routing['market'] ?? 'TR',
            'offers' => [
                $makeOffer(1, $origin === 'IST' ? 'Turkish Airlines' : 'Mock Air', 'TK1981', 245, $basePrice),
                $makeOffer(2, 'British Airways', 'BA675', 255, $basePrice + 210),
            ],
        ];
    }

    protected function mockQuote(array $payload): array
    {
        $offer = Arr::get($payload, 'offer', []);
        $fare = Arr::get($payload, 'selected_fare', []);
        $price = (float) (Arr::get($fare, 'total_amount') ?: Arr::get($offer, 'price.amount') ?: Arr::get($offer, 'total_amount') ?: 0);
        $currency = Arr::get($fare, 'currency') ?: Arr::get($offer, 'currency', 'TRY');

        return [
            'quote_id' => 'mock_quote_'.Str::uuid(),
            'supplier_code' => Arr::get($offer, 'supplier_context.supplier_code') ?: Arr::get($offer, 'provider', 'mock'),
            'confirmed_price' => [
                'amount' => $price,
                'currency' => $currency,
            ],
            'price_changed' => false,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'booking_requirements' => [
                'contact' => ['email' => true, 'phone' => true],
                'traveller' => [
                    'birth_date' => true,
                    'gender' => false,
                    'nationality' => false,
                    'passport_number' => false,
                    'passport_expiry' => false,
                ],
            ],
            'rules' => Arr::get($offer, 'rules', []),
            'checkout_fields' => [],
            'raw' => ['mode' => 'mock'],
        ];
    }

    protected function mockBook(array $payload): array
    {
        return [
            'booking_status' => 'confirmed',
            'fulfillment_status' => 'ticket_issued',
            'supplier_booking_reference' => 'MOCK-'.strtoupper(Str::random(8)),
            'pnr' => strtoupper(Str::random(6)),
            'ticket_numbers' => ['TK'.random_int(1000000000, 9999999999)],
            'manual_action_required' => false,
            'raw' => ['mode' => 'mock'],
        ];
    }
}
