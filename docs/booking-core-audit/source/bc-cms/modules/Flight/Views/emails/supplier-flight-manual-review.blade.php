@extends('Email::layout')

@section('content')
@php
    $isAdmin = ($recipientType ?? 'customer') === 'admin';

    $supplierBooking = $supplierBooking ?? null;
    $quote = $quote ?? null;
    $offer = $quote ? $quote->offer : null;

    $status = optional($supplierBooking)->fulfillment_status ?: $booking->getMeta('tsa_fulfillment_status', 'manual_review_required');
    $manualReview = optional($supplierBooking)->manual_review_required || $status === 'manual_review_required';

    $route = trim((optional($offer)->origin ?: '-') . ' → ' . (optional($offer)->destination ?: '-'));
    $departure = optional(optional($offer)->departure_at)->format('d.m.Y H:i');
    $arrival = optional(optional($offer)->arrival_at)->format('d.m.Y H:i');

    $adminUrl = optional($supplierBooking)->id
        ? route('flight.admin.supplier-bookings.detail', $supplierBooking->id)
        : route('report.admin.booking');

    $customerUrl = $booking->getDetailUrl();
@endphp

<div class="b-container">
    <div class="b-panel">
        @if($isAdmin)
            <h2>{{ __('Paid flight booking requires manual review') }}</h2>
            <p>{{ __('A supplier flight booking has received payment but requires manual operational review before ticketing is completed.') }}</p>
        @else
            <h2>{{ __('We received your payment') }}</h2>
            <p>{{ __('Your flight booking is being processed by our operation team.') }}</p>
        @endif

        <table class="b-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="label">{{ __('Booking Code') }}</td>
                <td class="val">{{ $booking->code }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Booking Status') }}</td>
                <td class="val">{{ $booking->status }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Payment Status') }}</td>
                <td class="val">{{ optional($booking->payment)->status ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Payment Method') }}</td>
                <td class="val">{{ $booking->gateway ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Paid Amount') }}</td>
                <td class="val">{{ $booking->paid }} {{ $booking->currency }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Route') }}</td>
                <td class="val">{{ $route }}</td>
            </tr>
            @if($departure)
                <tr>
                    <td class="label">{{ __('Departure') }}</td>
                    <td class="val">{{ $departure }}</td>
                </tr>
            @endif
            @if($arrival)
                <tr>
                    <td class="label">{{ __('Arrival') }}</td>
                    <td class="val">{{ $arrival }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">{{ __('Supplier') }}</td>
                <td class="val">{{ optional($supplierBooking)->supplier_code ?: optional($offer)->supplier_code ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Ticketing Status') }}</td>
                <td class="val">{{ $status ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('Manual Review') }}</td>
                <td class="val">{{ $manualReview ? __('Yes') : __('No') }}</td>
            </tr>
            @if($reasonCode)
                <tr>
                    <td class="label">{{ __('Reason') }}</td>
                    <td class="val">{{ $reasonCode }}</td>
                </tr>
            @endif
        </table>

        @if($isAdmin)
            <p>
                <strong>{{ __('Required action') }}:</strong>
                {{ __('Open the supplier booking detail, review payment and supplier status, then retry ticketing or complete the supplier booking manually.') }}
            </p>
            <p>
                <a href="{{ $adminUrl }}">{{ __('Open supplier booking in admin') }}</a>
            </p>
        @else
            <p>
                {{ __('Your payment was received. Ticketing needs manual review. Our operation team will follow up. No duplicate supplier ticketing will be attempted automatically.') }}
            </p>
            <p>
                <a href="{{ $customerUrl }}">{{ __('View your booking') }}</a>
            </p>
        @endif

        <br>
        <p>{{ __('Regards') }},<br>{{ setting_item('site_title', config('app.name')) }}</p>
    </div>
</div>
@endsection
