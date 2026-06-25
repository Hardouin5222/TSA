<?php

namespace Modules\Flight\Services;

use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Flight\Models\SupplierOffer;
use Modules\Flight\Models\SupplierQuote;
use Modules\Flight\Models\SupplierBooking;

class SupplierFlightService
{
    public function isBookable(SupplierOffer $offer): bool
    {
        $quote = $offer->latestQuote;
        if (!$quote || $quote->isExpired()) {
            return false;
        }
        return in_array($quote->status, ['quoted', 'checkout_started'], true);
    }

    public function filterCheckoutValidate(SupplierOffer $offer, Request $request, array $rules): array
    {
        $quote = $this->resolveQuote($offer, $request);
        $requirements = $quote ? ($quote->requirements_json ?: []) : [];
        $travellerReq = data_get($requirements, 'traveller', []);

        $rules['travellers'] = 'required|array|min:1';
        $rules['travellers.*.first_name'] = 'required|string|max:100';
        $rules['travellers.*.last_name'] = 'required|string|max:100';

        if (data_get($travellerReq, 'birth_date')) {
            $rules['travellers.*.birth_date'] = 'required|date';
        }
        if (data_get($travellerReq, 'gender')) {
            $rules['travellers.*.gender'] = 'required|string|max:20';
        }
        if (data_get($travellerReq, 'nationality')) {
            $rules['travellers.*.nationality'] = 'required|string|max:3';
        }
        if (data_get($travellerReq, 'passport_number')) {
            $rules['travellers.*.passport_number'] = 'required|string|max:50';
        }
        if (data_get($travellerReq, 'passport_expiry')) {
            $rules['travellers.*.passport_expiry'] = 'required|date';
        }

        return $rules;
    }

    public function beforeCheckout(SupplierOffer $offer, Request $request, Booking $booking)
    {
        $quote = $this->resolveQuote($offer, $request, $booking);
        if (!$quote) {
            return response()->json(['status' => 0, 'message' => __('Flight quote not found')], 422);
        }
        if ($quote->isExpired()) {
            return response()->json(['status' => 0, 'message' => __('This flight price has expired. Please search again.')], 422);
        }

        $booking->total = $quote->confirmed_total_amount;
        $booking->currency = $quote->confirmed_currency;
        $booking->addMeta('tsa_supplier_quote_uuid', $quote->quote_uuid);
        $booking->addMeta('tsa_supplier_quote_snapshot', $quote->payload_json ?: []);
        $booking->save();

        return null;
    }

    public function afterCheckout(SupplierOffer $offer, Request $request, Booking $booking)
    {
        $quote = $this->resolveQuote($offer, $request, $booking);

        $snapshot = [
            'offer' => $offer->payload_json,
            'quote' => $quote ? $quote->payload_json : null,
            'travellers' => $request->input('travellers', []),
            'contact' => [
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ],
            'billing' => $request->only(['invoice_type', 'tax_number', 'company_name', 'address_line_1', 'address_line_2', 'city', 'country']),
        ];

        $booking->addMeta('tsa_product_type', 'flight');
        $booking->addMeta('tsa_supplier_snapshot', $snapshot);
        $booking->addMeta('tsa_fulfillment_status', 'payment_pending');

        SupplierBooking::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'quote_id' => $quote ? $quote->id : null,
                'quote_uuid' => $quote ? $quote->quote_uuid : null,
                'supplier_code' => $offer->supplier_code,
                'payment_status' => 'payment_pending',
                'fulfillment_status' => 'payment_pending',
                'manual_review_required' => false,
                'snapshot_json' => $snapshot,
            ]
        );

        return null;
    }

    public function getBookingData(SupplierOffer $offer): array
    {
        return [
            'title' => $offer->display_name,
            'price' => $offer->total_amount,
            'currency' => $offer->currency,
            'origin' => $offer->origin,
            'destination' => $offer->destination,
            'departure_at' => optional($offer->departure_at)->toDateTimeString(),
            'arrival_at' => optional($offer->arrival_at)->toDateTimeString(),
            'payload' => $offer->payload_json,
        ];
    }

    protected function resolveQuote(SupplierOffer $offer, Request $request, ?Booking $booking = null): ?SupplierQuote
    {
        $quoteUuid = $request->input('tsa_quote_uuid');

        if (!$quoteUuid && $booking) {
            $quoteUuid = $booking->getMeta('tsa_supplier_quote_uuid');
        }

        // 1) Önce ideal eşleşme: quote_uuid + offer_id
        if ($quoteUuid) {
            $quote = SupplierQuote::where('quote_uuid', $quoteUuid)
                ->where('offer_id', $offer->id)
                ->latest('id')
                ->first();

            if ($quote) {
                return $quote;
            }

            // 2) Eğer offer_id eşleşmesi kaçarsa, quote_uuid tek başına dene.
            // Bu quote_uuid bizim local unique değerimiz olduğu için güvenli.
            $quote = SupplierQuote::where('quote_uuid', $quoteUuid)
                ->latest('id')
                ->first();

            if ($quote) {
                return $quote;
            }
        }

        // 3) Booking object_id üzerinden quote bul.
        if ($booking && $booking->object_id) {
            $quote = SupplierQuote::where('offer_id', $booking->object_id)
                ->latest('id')
                ->first();

            if ($quote) {
                return $quote;
            }
        }

        // 4) Son fallback: offer modelinin son quote'u.
        return $offer->latestQuote;
    }
}
