<?php

namespace Modules\Flight\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Flight\Models\SupplierOffer;
use Modules\Flight\Models\SupplierQuote;
use Modules\Flight\Services\FlightSearchManager;
use Modules\Flight\Services\TsaSupplierBridgeClient;
use Modules\Flight\Services\TsaFlightSupplierResolver;

class SupplierCheckoutController extends Controller
{
    protected FlightSearchManager $searchManager;
    protected TsaSupplierBridgeClient $bridgeClient;
    protected TsaFlightSupplierResolver $supplierResolver;

    public function __construct(FlightSearchManager $searchManager, TsaSupplierBridgeClient $bridgeClient, TsaFlightSupplierResolver $supplierResolver)
    {
        $this->searchManager = $searchManager;
        $this->bridgeClient = $bridgeClient;
        $this->supplierResolver = $supplierResolver;
    }

    public function quote(Request $request)
    {
        $request->validate([
            'selected_offer' => 'required|string',
            'selected_fare' => 'required|string',
            'origin' => 'required|string|max:16',
            'destination' => 'required|string|max:16',
            'departure_date' => 'required|date',
            'adult_count' => 'nullable|integer|min:1|max:9',
            'child_count' => 'nullable|integer|min:0|max:9',
        ]);

        $selectedOfferId = (string) $request->input('selected_offer');
        $selectedFareId = (string) $request->input('selected_fare');

        $searchCriteria = $this->quoteSearchCriteria($request);
        $supplierData = $this->searchManager->search($searchCriteria);

        $offer = collect($supplierData['offers'] ?? [])->firstWhere('id', $selectedOfferId);

        if (!$offer) {
            $offer = $this->fallbackOfferForDirectQuote($request, $selectedOfferId, $selectedFareId);
        }

        if (!$offer) {
            return back()->with('error', __('The selected flight offer is no longer available. Please search again.'));
        }

        $fare = collect($offer['fare_options'] ?? [])->firstWhere('id', $selectedFareId);

        if (!$fare) {
            $fare = $this->fallbackFareForDirectQuote($request, $selectedFareId);
        }

        if (!$fare) {
            return back()->with('error', __('The selected fare package is no longer available. Please choose another package.'));
        }

        $quotePayload = [
            'search_id' => $supplierData['search_id'] ?? null,
            'offer_id' => $offer['id'],
            'selected_fare_id' => $fare['id'],
            'offer' => $offer,
            'selected_fare' => $fare,
            'passenger_summary' => [
                'adult_count' => (int) $request->input('adult_count', 1),
                'child_count' => (int) $request->input('child_count', 0),
                'infant_count' => (int) $request->input('infant_count', 0),
            ],
            'supplier_context' => Arr::get($offer, 'supplier_context', []),
        ];

        $quotePayload = $this->supplierResolver->decorateQuotePayload($quotePayload, $offer, $request->all());

        if (!$this->supplierResolver->canProceedToCheckout($quotePayload)) {
            return back()->with('error', __('This route is not enabled for online ticketing yet. Our team must confirm it manually.'));
        }

        try {
            $quoteResponse = $this->bridgeClient->quote($quotePayload);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', __('We could not confirm this flight price. Please try again.'));
        }

        $latestOffer = Arr::get($quoteResponse, 'latest_offer');
        if (is_array($latestOffer) && !empty($latestOffer)) {
            $offer = array_replace_recursive($offer, $latestOffer);
            $offer['id'] = Arr::get($offer, 'id') ?: $selectedOfferId;
            $offer['offer_id'] = Arr::get($offer, 'offer_id') ?: $selectedOfferId;
            $offer['supplier_offer_id'] = Arr::get($offer, 'supplier_offer_id') ?: $selectedOfferId;
            $offer['supplier_code'] = Arr::get($offer, 'supplier_code') ?: Arr::get($quoteResponse, 'supplier_code');
            $offer['supplier'] = Arr::get($offer, 'supplier') ?: Arr::get($quoteResponse, 'supplier_code');
            $offer['provider'] = Arr::get($offer, 'provider') ?: Arr::get($quoteResponse, 'supplier_code');
            $offer['total_amount'] = Arr::get($quoteResponse, 'confirmed_total_amount') ?: Arr::get($offer, 'total_amount');
            $offer['price'] = Arr::get($quoteResponse, 'confirmed_total_amount') ?: Arr::get($offer, 'price');
            $offer['currency'] = Arr::get($quoteResponse, 'currency') ?: Arr::get($offer, 'currency');

            $latestFare = collect($offer['fare_options'] ?? [])->firstWhere('id', $selectedFareId)
                ?: collect($offer['fare_options'] ?? [])->first();

            if ($latestFare) {
                $fare = array_replace($fare, (array) $latestFare);
            }

            $fare['id'] = Arr::get($fare, 'id') ?: $selectedFareId;
            $fare['fare_id'] = Arr::get($fare, 'fare_id') ?: $selectedFareId;
            $fare['total_amount'] = Arr::get($quoteResponse, 'confirmed_total_amount') ?: Arr::get($fare, 'total_amount') ?: Arr::get($fare, 'price') ?: 0;
            $fare['price'] = Arr::get($quoteResponse, 'confirmed_total_amount') ?: Arr::get($fare, 'price') ?: Arr::get($fare, 'total_amount') ?: 0;
            $fare['currency'] = Arr::get($quoteResponse, 'currency') ?: Arr::get($fare, 'currency') ?: Arr::get($offer, 'currency', 'USD');
        }

        return DB::transaction(function () use ($request, $offer, $fare, $quoteResponse, $quotePayload) {
            $checkoutExpiresAt = now()->addHours(2);

            $offerModel = SupplierOffer::create([
                'offer_uuid' => (string) Str::uuid(),
                'supplier_code' => Arr::get($quoteResponse, 'supplier_code') ?: Arr::get($offer, 'provider', 'mock'),
                'supplier_offer_id' => Arr::get($offer, 'supplier_offer_id') ?: Arr::get($offer, 'id'),
                'origin' => Arr::get($offer, 'origin.code') ?: Arr::get($offer, 'origin') ?: $request->input('origin'),
                'destination' => Arr::get($offer, 'destination.code') ?: Arr::get($offer, 'destination') ?: $request->input('destination'),
                'departure_at' => Arr::get($offer, 'departure_at'),
                'arrival_at' => Arr::get($offer, 'arrival_at'),
                'currency' => Arr::get($quoteResponse, 'currency')
                    ?: Arr::get($quoteResponse, 'confirmed_price.currency')
                    ?: Arr::get($fare, 'currency')
                    ?: Arr::get($offer, 'currency', 'USD'),

                'total_amount' => (float) (
                    Arr::get($quoteResponse, 'confirmed_total_amount')
                    ?: Arr::get($quoteResponse, 'confirmed_price.amount')
                    ?: (is_numeric(Arr::get($quoteResponse, 'confirmed_price')) ? Arr::get($quoteResponse, 'confirmed_price') : null)
                    ?: Arr::get($fare, 'price')
                    ?: Arr::get($fare, 'total_amount')
                    ?: Arr::get($offer, 'price')
                    ?: Arr::get($offer, 'total_amount')
                    ?: 0
                ),
                'payload_json' => $offer,
                'supplier_context_json' => Arr::get($quotePayload, 'supplier_context', Arr::get($offer, 'supplier_context', [])),
                'expires_at' => $checkoutExpiresAt,
                'status' => 'quoted',
            ]);

            $supplierQuoteReference = Arr::get($quoteResponse, 'quote_id')
                ?: Arr::get($quoteResponse, 'quote_uuid');

            $quoteUuid = (string) Str::uuid();



            $quote = SupplierQuote::create([
                'quote_uuid' => $quoteUuid,
                'offer_id' => $offerModel->id,
                'offer_uuid' => $offerModel->offer_uuid,
                'selected_fare_id' => $fare['id'],
                'supplier_code' => $offerModel->supplier_code,
                'confirmed_currency' => Arr::get($quoteResponse, 'currency')
                    ?: Arr::get($quoteResponse, 'confirmed_price.currency')
                    ?: $offerModel->currency,

                'confirmed_total_amount' => (float) (
                    Arr::get($quoteResponse, 'confirmed_total_amount')
                    ?: Arr::get($quoteResponse, 'confirmed_price.amount')
                    ?: (is_numeric(Arr::get($quoteResponse, 'confirmed_price')) ? Arr::get($quoteResponse, 'confirmed_price') : null)
                    ?: $offerModel->total_amount
                ),
                'price_changed' => (bool) Arr::get($quoteResponse, 'price_changed', false),
                'requirements_json' => Arr::get($quoteResponse, 'booking_requirements', []),
                'rules_json' => Arr::get($quoteResponse, 'rules', []),
                'payload_json' => [
                    'supplier_quote_reference' => $supplierQuoteReference,
                    'quote_response' => $quoteResponse,
                    'supplier_context' => Arr::get($quotePayload, 'supplier_context', []),
                    'selected_fare' => $fare,
                    'offer_snapshot' => $offer,
                ],
                'expires_at' => $checkoutExpiresAt,
                'status' => 'quoted',
            ]);

            $booking = new Booking();
            $booking->object_id = $offerModel->id;
            $booking->object_model = 'tsa_supplier_flight';
            $booking->status = Booking::DRAFT;
            $booking->total = $quote->confirmed_total_amount;
            $booking->total_before_fees = $quote->confirmed_total_amount;
            $booking->currency = $quote->confirmed_currency;
            $booking->start_date = $offerModel->departure_at;
            $booking->end_date = $offerModel->arrival_at;
            $booking->total_guests = (int) $request->input('adult_count', 1) + (int) $request->input('child_count', 0);
            $booking->customer_id = Auth::id();
            $booking->vendor_id = null;
            $booking->save();

            $booking->addMeta('tsa_product_type', 'flight');
            $booking->addMeta('tsa_supplier_offer_uuid', $offerModel->offer_uuid);
            $booking->addMeta('tsa_supplier_quote_uuid', $quote->quote_uuid);
            $booking->addMeta('tsa_supplier_quote_reference', $supplierQuoteReference);
            $booking->addMeta('tsa_selected_fare_id', $fare['id']);
            $booking->addMeta('tsa_supplier_offer_snapshot', $offer);
            $booking->addMeta('tsa_supplier_quote_snapshot', $quote->payload_json);
            $booking->addMeta('tsa_fulfillment_status', 'checkout_started');

            $quote->status = 'checkout_started';
            $quote->save();

            return redirect($booking->getCheckoutUrl());
        });
    }
    protected function quoteSearchCriteria(Request $request): array
    {
        return array_filter([
            'origin' => strtoupper(trim((string) $request->input('origin'))),
            'destination' => strtoupper(trim((string) $request->input('destination'))),
            'departure_date' => $request->input('departure_date'),
            'return_date' => $request->input('return_date'),
            'adult_count' => (int) $request->input('adult_count', 1),
            'child_count' => (int) $request->input('child_count', 0),
            'infant_count' => (int) $request->input('infant_count', 0),
            'cabin_class' => $request->input('cabin_class', 'economy'),
            'currency' => $request->input('currency', 'USD'),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    protected function fallbackOfferForDirectQuote(Request $request, string $selectedOfferId, string $selectedFareId): ?array
    {
        if (!$selectedOfferId || !Str::startsWith($selectedOfferId, 'off_')) {
            return null;
        }

        $supplierCode = 'DUFFEL_SANDBOX';
        $currency = strtoupper((string) $request->input('currency', 'USD'));
        $fare = $this->fallbackFareForDirectQuote($request, $selectedFareId);

        return [
            'id' => $selectedOfferId,
            'offer_id' => $selectedOfferId,
            'template_id' => $selectedOfferId,
            'supplier_offer_id' => $selectedOfferId,
            'provider' => $supplierCode,
            'supplier' => $supplierCode,
            'supplier_code' => $supplierCode,
            'origin' => strtoupper((string) $request->input('origin')),
            'destination' => strtoupper((string) $request->input('destination')),
            'departure_at' => $request->input('departure_date'),
            'arrival_at' => null,
            'currency' => $currency,
            'price_currency' => $currency,
            'total_amount' => 0,
            'amount' => 0,
            'price' => 0,
            'fare_family' => 'Standard',
            'fare_options' => [$fare],
            'selected_fare' => $fare,
            'rules' => [],
            'capabilities' => [
                'instant_ticketing_supported' => true,
                'passport_required' => true,
                'birth_date_required' => true,
                'gender_required' => true,
                'nationality_required' => true,
            ],
            'supplier_context' => [
                'supplier_code' => $supplierCode,
                'raw_offer_id' => $selectedOfferId,
                'pricing_token' => $selectedOfferId,
                'quote_reference' => $selectedOfferId,
            ],
            'payload' => [
                'id' => $selectedOfferId,
                'offer_id' => $selectedOfferId,
                'supplier_code' => $supplierCode,
            ],
        ];
    }

    protected function fallbackFareForDirectQuote(Request $request, string $selectedFareId): array
    {
        $currency = strtoupper((string) $request->input('currency', 'USD'));

        return [
            'id' => $selectedFareId ?: 'standard',
            'fare_id' => $selectedFareId ?: 'standard',
            'label' => 'Standard',
            'name' => 'Standard',
            'currency' => $currency,
            'total_price' => 0,
            'total_amount' => 0,
            'price' => 0,
            'features' => ['Duffel live availability', 'Quote required before payment'],
        ];
    }

}
