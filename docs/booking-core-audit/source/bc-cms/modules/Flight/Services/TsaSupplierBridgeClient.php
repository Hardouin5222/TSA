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
            return ['offers' => []];
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
