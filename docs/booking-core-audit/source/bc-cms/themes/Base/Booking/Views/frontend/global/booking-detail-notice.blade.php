<?php

use Modules\Booking\Models\Booking;

$supplierBooking = null;
$isSupplierFlight = (($booking->object_model ?? null) === 'tsa_supplier_flight');

if ($isSupplierFlight && class_exists(\Modules\Flight\Models\SupplierBooking::class)) {
    $supplierBooking = \Modules\Flight\Models\SupplierBooking::where('booking_id', $booking->id)->latest('id')->first();
}

$fulfillmentStatus = $supplierBooking->fulfillment_status ?? null;
$paymentStatus = $supplierBooking->payment_status ?? null;
$isFinalized = $supplierBooking && in_array($fulfillmentStatus, ['ticket_issued', 'booking_confirmed'], true);
$needsManualReview = $supplierBooking && ($supplierBooking->manual_review_required || $fulfillmentStatus === 'manual_review_required');
$isTicketingProgress = $supplierBooking && in_array($fulfillmentStatus, ['ticketing_in_progress', 'manual_retry_queued'], true);
$isPaymentPending = $supplierBooking && $paymentStatus === 'payment_pending';

$noticeIcon = 'images/ico_success.svg';
$noticeAlt = 'Payment Success';
$noticeTitle = __('your booking was submitted successfully!');
$noticeLine2 = __('Booking details has been sent to:');
$noticeLine3 = null;

if ($isSupplierFlight) {
    if ($isFinalized) {
        $noticeIcon = 'images/ico_success.svg';
        $noticeAlt = 'Ticketing Completed';
        $noticeTitle = __('your booking is confirmed and ticketing is completed.');
        $noticeLine2 = __('Booking details has been sent to:');
        $noticeLine3 = __('PNR and ticket details are ready.');
    } elseif ($needsManualReview) {
        $noticeIcon = 'images/ico_warning.svg';
        $noticeAlt = 'Manual Review Required';
        $noticeTitle = __('your payment was received. Ticketing needs manual review.');
        $noticeLine2 = __('Our operation team will follow up. Booking details has been sent to:');
        $noticeLine3 = __('No duplicate supplier ticketing will be attempted automatically.');
    } elseif ($isTicketingProgress) {
        $noticeIcon = 'images/ico_warning.svg';
        $noticeAlt = 'Ticketing In Progress';
        $noticeTitle = __('your payment was received. Ticketing is in progress.');
        $noticeLine2 = __('Booking details has been sent to:');
        $noticeLine3 = __('Ticketing result will be updated after supplier confirmation.');
    } elseif ($isPaymentPending) {
        $noticeIcon = 'images/ico_warning.svg';
        $noticeAlt = 'Payment Pending';
        $noticeTitle = __('your booking was received. Payment is pending.');
        $noticeLine2 = __('Ticketing will start after payment is confirmed. Booking details has been sent to:');
        $noticeLine3 = __('Please complete payment to continue supplier ticketing.');
    }
} elseif (in_array($booking->status, ['cancelled', 'unpaid', Booking::DRAFT], true)) {
    $noticeIcon = 'images/ico_warning.svg';
    $noticeAlt = 'Payment Status';
    $noticeTitle = __('Your Booking Order is :name yet!', ['name' => $booking->status_name]);
    $noticeLine2 = __('The order detail is saved in the Booking history.');
}

?>
<div class="row booking-success-notice">
    <div class="col-lg-8 col-md-8">
        <div class="d-flex align-items-center">
            <img src="{{ url($noticeIcon) }}" alt="{{ $noticeAlt }}">
            <div class="notice-success">
                <p class="line1"><span>{{$booking->first_name}},</span>
                    {{ $noticeTitle }}
                </p>
                <p class="line2">{{ $noticeLine2 }} @if(!empty($booking->email)) <span>{{$booking->email}}</span>@endif</p>
                @if(!empty($noticeLine3))
                    <div class="line2">{{ $noticeLine3 }}</div>
                @endif
                @if(!empty($gateway) && ($note = $gateway->getOption("payment_note")))
                    <div class="line2">{!! clean($note) !!}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4">
        <ul class="booking-info-detail">
            <li><span>{{__('Booking Number')}}:</span> {{$booking->id}}</li>
            <li><span>{{__('Booking Date')}}:</span> {{display_date($booking->created_at)}}</li>
            @if(!empty($gateway))
                <li><span>{{__('Payment Method')}}:</span> {{$gateway->name}}</li>
            @endif
            <li><span>{{__('Booking Status')}}:</span> <span class="badge badge-primary badge-{{ $booking->status }}">{{$booking->status_name}}</span></li>
            @if($isSupplierFlight && $supplierBooking)
                <li><span>{{__('Ticketing Status')}}:</span> {{ str_replace('_', ' ', $fulfillmentStatus ?: '-') }}</li>
                @if(!empty($supplierBooking->pnr))
                    <li><span>{{__('PNR')}}:</span> {{ $supplierBooking->pnr }}</li>
                @endif
            @endif
        </ul>
    </div>
</div>
