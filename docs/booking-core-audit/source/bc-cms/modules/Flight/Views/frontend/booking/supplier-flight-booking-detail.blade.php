@php
    $offer = $service ?? null;

    $quoteUuid = $booking->getMeta('tsa_supplier_quote_uuid');
    $quote = $quoteUuid
        ? \Modules\Flight\Models\SupplierQuote::where('quote_uuid', $quoteUuid)->first()
        : null;

    $supplierBooking = \Modules\Flight\Models\SupplierBooking::where('booking_id', $booking->id)
        ->latest('id')
        ->first();

    $payload = $offer ? ($offer->payload_json ?? []) : [];

    $fulfillmentStatus = $supplierBooking->fulfillment_status
        ?? $booking->getMeta('tsa_fulfillment_status');

    $supplierReference = $supplierBooking->supplier_booking_reference
        ?? $booking->getMeta('tsa_supplier_booking_reference');

    $pnr = $supplierBooking->pnr
        ?? $booking->getMeta('tsa_pnr');

    $ticketNumbers = $supplierBooking->ticket_numbers_json ?? [];

    if (empty($ticketNumbers)) {
        $ticketNumbers = $booking->getJsonMeta('tsa_ticket_numbers', []);
    }

    if (is_string($ticketNumbers)) {
        $decodedTicketNumbers = json_decode($ticketNumbers, true);
        $ticketNumbers = is_array($decodedTicketNumbers) ? $decodedTicketNumbers : [];
    }

    $manualReview = (bool) ($supplierBooking->manual_review_required ?? false)
        || $fulfillmentStatus === 'manual_review_required';

    $isTicketed = in_array($fulfillmentStatus, ['ticket_issued', 'booking_confirmed'], true);

    $statusLabel = $fulfillmentStatus
        ? ucfirst(str_replace('_', ' ', $fulfillmentStatus))
        : __('Pending');

    $payment = $booking->payment_id
        ? \Modules\Booking\Models\Payment::find($booking->payment_id)
        : null;

    $paymentStatus = $payment->status ?? null;
    $supplierPaymentStatus = $supplierBooking->payment_status ?? null;

    $canContinuePaytrPayment =
        ($booking->gateway === 'paytr_iframe')
        && in_array($booking->status, ['processing', 'unpaid'], true)
        && ($paymentStatus === 'processing')
        && (
            $supplierPaymentStatus === 'payment_pending'
            || $fulfillmentStatus === 'payment_pending'
        );

    $paytrPaymentUrl = ($canContinuePaytrPayment && $payment)
        ? url('/booking/confirm/paytr_iframe?booking_code=' . rawurlencode($booking->code) . '&pid=' . rawurlencode($payment->code))
        : null;
@endphp

<div class="booking-review supplier-flight-review">
    <h4>{{ __('Flight Summary') }}</h4>

    <div class="booking-review-content">
        <p>
            <strong>{{ __('Route') }}:</strong>
            {{ $offer->origin ?? '-' }} → {{ $offer->destination ?? '-' }}
        </p>

        <p>
            <strong>{{ __('Departure') }}:</strong>
            {{ optional($offer->departure_at ?? null)->format('d.m.Y H:i') }}
        </p>

        <p>
            <strong>{{ __('Arrival') }}:</strong>
            {{ optional($offer->arrival_at ?? null)->format('d.m.Y H:i') }}
        </p>

        <p>
            <strong>{{ __('Supplier') }}:</strong>
            {{ strtoupper($offer->supplier_code ?? '-') }}
        </p>

        @if($quote)
            <p>
                <strong>{{ __('Quote expires') }}:</strong>
                {{ optional($quote->expires_at)->format('d.m.Y H:i') }}
            </p>

            @if($quote->price_changed)
                <div class="alert alert-warning">
                    {{ __('The supplier updated this price before checkout. The confirmed price is shown below.') }}
                </div>
            @endif
        @endif

        <hr>

        <p class="d-flex justify-content-between">
            <span>{{ __('Total') }}</span>
            <strong>{{ format_money($booking->total) }}</strong>
        </p>

        <hr>

        <p>
            <strong>{{ __('Ticketing Status') }}:</strong>
            <span class="badge {{ $isTicketed ? 'badge-success' : ($manualReview ? 'badge-warning' : 'badge-primary') }}">
                {{ $statusLabel }}
            </span>
        </p>

        @if($supplierReference)
            <p>
                <strong>{{ __('Supplier Reference') }}:</strong>
                {{ $supplierReference }}
            </p>
        @endif

        @if($pnr)
            <p>
                <strong>{{ __('PNR') }}:</strong>
                {{ $pnr }}
            </p>
        @endif

        @if(!empty($ticketNumbers))
            <p>
                <strong>{{ __('Ticket Numbers') }}:</strong>
            </p>
            <ul class="mb-2">
                @foreach($ticketNumbers as $ticketNumber)
                    <li>{{ $ticketNumber }}</li>
                @endforeach
            </ul>
        @endif

        @if($canContinuePaytrPayment && $paytrPaymentUrl)
            <div class="alert alert-info mt-2">
                <strong>{{ __('Payment is waiting.') }}</strong><br>
                {{ __('Your ticketing will start after PayTR confirms your payment.') }}

                <div class="mt-2">
                    <a href="{{ $paytrPaymentUrl }}" class="btn btn-primary btn-sm">
                        {{ __('Continue PayTR Payment') }}
                    </a>
                </div>
            </div>
        @elseif($manualReview)
            <div class="alert alert-warning mt-2">
                {{ __('Your payment was received. Ticketing needs manual review. Our operation team will follow up.') }}
            </div>
        @elseif($isTicketed)
            <div class="alert alert-success mt-2">
                {{ __('Your ticketing has been completed successfully.') }}
            </div>
        @else
            <small class="text-muted">
                {{ __('Ticketing starts after successful payment. If supplier confirmation needs manual review, our operation team will follow up.') }}
            </small>
        @endif
    </div>
</div>
