@php
    $offer = $service ?? null;

    $supplierBooking = null;
    if (class_exists(\Modules\Flight\Models\SupplierBooking::class)) {
        $supplierBooking = \Modules\Flight\Models\SupplierBooking::where('booking_id', $booking->id)->latest('id')->first();
    }

    $quoteUuid = method_exists($booking, 'getMeta') ? $booking->getMeta('tsa_supplier_quote_uuid', '') : '';
    $quote = null;
    if ($quoteUuid && class_exists(\Modules\Flight\Models\SupplierQuote::class)) {
        $quote = \Modules\Flight\Models\SupplierQuote::where('quote_uuid', $quoteUuid)->first();
    }

    if (!$quote && $supplierBooking && method_exists($supplierBooking, 'quote')) {
        $quote = $supplierBooking->quote;
    }

    $fulfillmentStatus = method_exists($booking, 'getMeta') ? $booking->getMeta('tsa_fulfillment_status', '') : '';
    $fulfillmentStatus = $fulfillmentStatus ?: optional($supplierBooking)->fulfillment_status;

    $supplierRef = method_exists($booking, 'getMeta') ? $booking->getMeta('tsa_supplier_booking_reference', '') : '';
    $supplierRef = $supplierRef ?: optional($supplierBooking)->supplier_booking_reference;

    $pnr = method_exists($booking, 'getMeta') ? $booking->getMeta('tsa_pnr', '') : '';
    $pnr = $pnr ?: optional($supplierBooking)->pnr;

    $tickets = [];
    if (method_exists($booking, 'getJsonMeta')) {
        $tickets = $booking->getJsonMeta('tsa_ticket_numbers', []);
    }
    if (empty($tickets) && $supplierBooking && is_array($supplierBooking->ticket_numbers_json ?? null)) {
        $tickets = $supplierBooking->ticket_numbers_json;
    }
    $tickets = is_array($tickets) ? array_filter($tickets) : [];

    $statusText = $fulfillmentStatus ? ucwords(str_replace('_', ' ', $fulfillmentStatus)) : __('Pending');

    $isCompleted = in_array($fulfillmentStatus, ['ticket_issued', 'booking_confirmed'], true);
    $isManualReview = $fulfillmentStatus === 'manual_review_required' || optional($supplierBooking)->manual_review_required;
    $isPaymentPending = in_array($fulfillmentStatus, ['payment_pending', 'ticketing_pending'], true);

    $routeText = trim((optional($offer)->origin ?: '-') . ' → ' . (optional($offer)->destination ?: '-'));
@endphp

<div class="b-panel-title">{{ __('Flight information') }}</div>

<div class="b-table-wrap">
    <table class="b-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label">{{ __('Booking Number') }}</td>
            <td class="val">{{ $booking->id }}</td>
        </tr>

        <tr>
            <td class="label">{{ __('Booking Status') }}</td>
            <td class="val">{{ $booking->statusName }}</td>
        </tr>

        @if($booking->gatewayObj)
            <tr>
                <td class="label">{{ __('Payment method') }}</td>
                <td class="val">{{ $booking->gatewayObj->getOption('name') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">{{ __('Route') }}</td>
            <td class="val">{{ $routeText }}</td>
        </tr>

        @if(optional($offer)->departure_at)
            <tr>
                <td class="label">{{ __('Departure') }}</td>
                <td class="val">{{ optional($offer->departure_at)->format('d.m.Y H:i') }}</td>
            </tr>
        @endif

        @if(optional($offer)->arrival_at)
            <tr>
                <td class="label">{{ __('Arrival') }}</td>
                <td class="val">{{ optional($offer->arrival_at)->format('d.m.Y H:i') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">{{ __('Supplier') }}</td>
            <td class="val">{{ optional($offer)->supplier_code ?: optional($supplierBooking)->supplier_code ?: '-' }}</td>
        </tr>

        @if($quote)
            <tr>
                <td class="label">{{ __('Quote expires') }}</td>
                <td class="val">{{ optional($quote->expires_at)->format('d.m.Y H:i') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">{{ __('Total') }}</td>
            <td class="val"><strong>{{ format_money($booking->total) }}</strong></td>
        </tr>

        <tr>
            <td class="label">{{ __('Ticketing Status') }}</td>
            <td class="val"><strong>{{ $statusText }}</strong></td>
        </tr>

        @if($supplierRef)
            <tr>
                <td class="label">{{ __('Supplier Reference') }}</td>
                <td class="val">{{ $supplierRef }}</td>
            </tr>
        @endif

        @if($pnr)
            <tr>
                <td class="label">{{ __('PNR') }}</td>
                <td class="val">{{ $pnr }}</td>
            </tr>
        @endif

        @if(!empty($tickets))
            <tr>
                <td class="label">{{ __('Ticket Numbers') }}</td>
                <td class="val">
                    @foreach($tickets as $ticket)
                        <div>{{ $ticket }}</div>
                    @endforeach
                </td>
            </tr>
        @endif

        <tr>
            <td class="label">{{ __('Status Note') }}</td>
            <td class="val">
                @if($isCompleted)
                    {{ __('Your booking is confirmed and ticketing is completed. PNR and ticket details are ready.') }}
                @elseif($isManualReview)
                    {{ __('Your payment was received. Ticketing needs manual review. Our operation team will follow up. No duplicate supplier ticketing will be attempted automatically.') }}
                @elseif($isPaymentPending)
                    {{ __('Your booking was received. Ticketing will start after successful payment confirmation.') }}
                @else
                    {{ __('Your booking was received. Our operation team will follow up if supplier confirmation needs manual review.') }}
                @endif
            </td>
        </tr>
    </table>
</div>
