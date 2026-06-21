<?php

namespace Modules\Flight\Models;

use App\BaseModel;
use Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Flight\Services\SupplierFlightService;

class SupplierOffer extends BaseModel
{
    protected $table = 'bc_tsa_supplier_offers';

    protected $fillable = [
        'offer_uuid',
        'supplier_code',
        'supplier_offer_id',
        'origin',
        'destination',
        'departure_at',
        'arrival_at',
        'currency',
        'total_amount',
        'payload_json',
        'supplier_context_json',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'supplier_context_json' => 'array',
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'expires_at' => 'datetime',
        'total_amount' => 'float',
    ];

    public $checkout_form_file = 'Flight::frontend.booking.supplier-flight-checkout-form';
    public $checkout_booking_detail_file = 'Flight::frontend.booking.supplier-flight-booking-detail';

    public function quotes(): HasMany
    {
        return $this->hasMany(SupplierQuote::class, 'offer_id');
    }

    public function latestQuote()
    {
        return $this->hasOne(SupplierQuote::class, 'offer_id')->latestOfMany();
    }

    public function getDisplayNameAttribute(): string
    {
        $payload = $this->payload_json ?: [];
        $airline = data_get($payload, 'airline.name') ?: data_get($payload, 'airline') ?: $this->supplier_code;
        return trim($airline . ' ' . $this->origin . '-' . $this->destination);
    }

    public function getServiceTitle(): string
    {
        return $this->display_name;
    }

    public function getDetailUrl($full = true)
    {
        return route('flight.search', [
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure_date' => optional($this->departure_at)->format('Y-m-d'),
        ]);
    }

    public function getPrice(): float
    {
        return (float) $this->total_amount;
    }

    public function isBookable(): bool
    {
        return app(SupplierFlightService::class)->isBookable($this);
    }

    public function filterCheckoutValidate($request, array $rules): array
    {
        return app(SupplierFlightService::class)->filterCheckoutValidate($this, $request, $rules);
    }

    public function beforeCheckout($request, $booking)
    {
        return app(SupplierFlightService::class)->beforeCheckout($this, $request, $booking);
    }

    public function afterCheckout($request, $booking)
    {
        return app(SupplierFlightService::class)->afterCheckout($this, $request, $booking);
    }

    public function getBookingData(): array
    {
        return app(SupplierFlightService::class)->getBookingData($this);
    }
    public static function isEnable()
    {
        return false;
    }

    public function beforePaymentProcess($booking, $gateway = null)
    {
        $quote = $this->latestQuote
            ?: \Modules\Flight\Models\SupplierQuote::where('offer_id', $this->id)->latest('id')->first();

        if ($quote) {
            $booking->total = $quote->confirmed_total_amount;
            $booking->currency = $quote->confirmed_currency ?: $booking->currency;

            $booking->addMeta('tsa_supplier_quote_uuid', $quote->quote_uuid);
            $booking->addMeta('tsa_supplier_quote_snapshot', $quote->payload_json ?: []);

            $booking->save();
        }

        return null;
    }

    public function afterPaymentProcess($booking, $gateway = null)
    {
        $result = app(\Modules\Flight\Services\SupplierFlightService::class)
            ->afterCheckout($this, request(), $booking);

        // Critical safety rule:
        // Do not start supplier ticketing before real payment is confirmed.
        // Offline / processing / unpaid bookings must stay pending.
        $paidStatuses = [
            Booking::PAID,
            Booking::COMPLETED,
            Booking::CONFIRMED,
        ];

        if (!in_array($booking->status, $paidStatuses, true)) {
            $booking->addMeta('tsa_fulfillment_status', 'payment_pending');
            $booking->save();

            return $result;
        }

        event(new \Modules\Flight\Events\SupplierPaymentConfirmed(
            $booking->id,
            $booking->gateway ?: 'offline',
            [
                'gateway' => $booking->gateway ?: 'offline',
                'booking_code' => $booking->code,
                'payment_id' => 'BC-' . $booking->id . '-' . time(),
            ]
        ));

        return $result;
    }
}
