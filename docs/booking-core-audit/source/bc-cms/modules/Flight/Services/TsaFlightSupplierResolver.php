<?php

namespace Modules\Flight\Services;

use Illuminate\Support\Arr;

class TsaFlightSupplierResolver
{
    protected array $turkeyAirportCodes = [
        'IST', 'SAW', 'ESB', 'ADB', 'AYT', 'DLM', 'BJV', 'COV',
        'TZX', 'GZT', 'ASR', 'KYA', 'SZF', 'DIY', 'VAN', 'ERZ',
        'HTY', 'MLX', 'MQM', 'BAL', 'EZS', 'KZR', 'NAV', 'OGU',
        'RZV', 'VAS', 'ERC', 'ADF', 'IGD', 'ISE', 'KSY', 'USQ',
    ];

    public function resolveForQuote(array $offer, array $requestData = []): array
    {
        $origin = $this->airportCode(
            Arr::get($offer, 'origin.code')
            ?: Arr::get($offer, 'origin')
            ?: Arr::get($requestData, 'origin')
        );

        $destination = $this->airportCode(
            Arr::get($offer, 'destination.code')
            ?: Arr::get($offer, 'destination')
            ?: Arr::get($requestData, 'destination')
        );

        $searchSupplier = strtoupper((string) (
            Arr::get($offer, 'supplier_context.search_supplier')
            ?: Arr::get($offer, 'supplier_context.supplier_code')
            ?: Arr::get($offer, 'provider')
            ?: 'AMADEUS'
        ));

        $market = $this->isTurkeyMarket($origin, $destination) ? 'TR' : 'GLOBAL';

        if ($market === 'TR') {
            return [
                'market' => 'TR',
                'search_supplier' => $searchSupplier,
                'ticketing_supplier' => env('TSA_TR_TICKETING_SUPPLIER', 'BILETBANK'),
                'payment_provider' => env('TSA_TR_PAYMENT_PROVIDER', 'BILETBANK_POS'),
                'ticketing_mode' => env('TSA_TR_TICKETING_MODE', 'mock'),
                'checkout_enabled' => true,
                'price_validation_required' => true,
                'origin' => $origin,
                'destination' => $destination,
            ];
        }

        $globalEnabled = filter_var(env('TSA_GLOBAL_TICKETING_ENABLED', false), FILTER_VALIDATE_BOOL);

        return [
            'market' => 'GLOBAL',
            'search_supplier' => $searchSupplier,
            'ticketing_supplier' => $globalEnabled ? env('TSA_GLOBAL_TICKETING_SUPPLIER', 'MYSTIFLY') : 'OFFLINE',
            'payment_provider' => $globalEnabled ? env('TSA_GLOBAL_PAYMENT_PROVIDER', 'OFFLINE') : 'OFFLINE',
            'ticketing_mode' => $globalEnabled ? env('TSA_GLOBAL_TICKETING_MODE', 'mock') : 'offline',
            'checkout_enabled' => $globalEnabled,
            'price_validation_required' => true,
            'origin' => $origin,
            'destination' => $destination,
        ];
    }

    public function decorateQuotePayload(array $quotePayload, array $offer, array $requestData = []): array
    {
        $resolution = $this->resolveForQuote($offer, $requestData);

        $existingContext = Arr::get($quotePayload, 'supplier_context', []);
        if (!is_array($existingContext)) {
            $existingContext = [];
        }

        $quotePayload['supplier_resolution'] = $resolution;
        $quotePayload['ticketing_supplier'] = $resolution['ticketing_supplier'];
        $quotePayload['payment_provider'] = $resolution['payment_provider'];

        $quotePayload['supplier_context'] = array_merge($existingContext, [
            'search_supplier' => $resolution['search_supplier'],
            'ticketing_supplier' => $resolution['ticketing_supplier'],
            'payment_provider' => $resolution['payment_provider'],
            'market' => $resolution['market'],
            'ticketing_mode' => $resolution['ticketing_mode'],
            'checkout_enabled' => $resolution['checkout_enabled'],
            'price_validation_required' => $resolution['price_validation_required'],
            'origin' => $resolution['origin'],
            'destination' => $resolution['destination'],
        ]);

        return $quotePayload;
    }

    public function canProceedToCheckout(array $quotePayload): bool
    {
        return (bool) Arr::get($quotePayload, 'supplier_resolution.checkout_enabled', true);
    }

    protected function isTurkeyMarket(?string $origin, ?string $destination): bool
    {
        return in_array($origin, $this->turkeyAirportCodes, true)
            || in_array($destination, $this->turkeyAirportCodes, true);
    }

    protected function airportCode($value): ?string
    {
        if (!$value) {
            return null;
        }

        return strtoupper(substr(trim((string) $value), 0, 3));
    }
}
