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

        $supplierData = $this->searchManager->search($request->all());
        $offer = collect($supplierData['offers'] ?? [])->firstWhere('id', $request->input('selected_offer'));

        if (!$offer) {
            return back()->with('error', __('The selected flight offer is no longer available. Please search again.'));
        }

        $fare = collect($offer['fare_options'] ?? [])->firstWhere('id', $request->input('selected_fare'));
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

            $quoteUuid = 'tsa_quote_' . Str::uuid();



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
}
