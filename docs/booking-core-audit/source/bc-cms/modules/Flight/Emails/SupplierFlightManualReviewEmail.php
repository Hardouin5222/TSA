<?php

namespace Modules\Flight\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;
use Modules\Flight\Models\SupplierBooking;
use Modules\Flight\Models\SupplierQuote;

class SupplierFlightManualReviewEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?SupplierBooking $supplierBooking;
    public ?SupplierQuote $quote;
    public string $recipientType;
    public string $reasonCode;
    public array $context;

    public function __construct(
        Booking $booking,
        ?SupplierBooking $supplierBooking = null,
        ?SupplierQuote $quote = null,
        string $recipientType = 'customer',
        string $reasonCode = '',
        array $context = []
    ) {
        $this->booking = $booking;
        $this->supplierBooking = $supplierBooking;
        $this->quote = $quote;
        $this->recipientType = $recipientType;
        $this->reasonCode = $reasonCode;
        $this->context = $context;
    }

    public function build()
    {
        $subject = $this->recipientType === 'admin'
            ? __('Manual review required for paid flight booking :code', ['code' => $this->booking->code])
            : __('Payment received for your flight booking :code', ['code' => $this->booking->code]);

        return $this
            ->subject($subject)
            ->view('Flight::emails.supplier-flight-manual-review');
    }
}
