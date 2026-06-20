@php
    $offer = $service ?? null;
    $quoteUuid = $booking->getMeta('tsa_supplier_quote_uuid');
    $quote = $quoteUuid ? \Modules\Flight\Models\SupplierQuote::where('quote_uuid', $quoteUuid)->first() : null;
    $payload = $offer ? ($offer->payload_json ?: []) : [];
@endphp

<div class="booking-review supplier-flight-review">
    <h4>{{ __('Flight Summary') }}</h4>
    <div class="booking-review-content">
        <p><strong>{{ __('Route') }}:</strong> {{ $offer->origin ?? '' }} → {{ $offer->destination ?? '' }}</p>
        <p><strong>{{ __('Departure') }}:</strong> {{ optional($offer->departure_at ?? null)->format('d.m.Y H:i') }}</p>
        <p><strong>{{ __('Arrival') }}:</strong> {{ optional($offer->arrival_at ?? null)->format('d.m.Y H:i') }}</p>
        <p><strong>{{ __('Supplier') }}:</strong> {{ strtoupper($offer->supplier_code ?? '') }}</p>
        @if($quote)
            <p><strong>{{ __('Quote expires') }}:</strong> {{ optional($quote->expires_at)->format('d.m.Y H:i') }}</p>
            @if($quote->price_changed)
                <div class="alert alert-warning">{{ __('The supplier updated this price before checkout. The confirmed price is shown below.') }}</div>
            @endif
        @endif
        <hr>
        <p class="d-flex justify-content-between">
            <span>{{ __('Total') }}</span>
            <strong>{{ format_money($booking->total) }}</strong>
        </p>
        <small class="text-muted">{{ __('Ticketing starts after successful payment. If supplier confirmation needs manual review, our operation team will follow up.') }}</small>
    </div>
</div>
