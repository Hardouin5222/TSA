<?php

namespace Modules\Flight\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Booking\Models\Booking;
use Modules\Flight\Emails\SupplierFlightManualReviewEmail;
use Modules\Flight\Models\SupplierBooking;
use Modules\Flight\Models\SupplierQuote;

class SupplierFlightNotificationService
{
    public function sendManualReviewNotifications(
        Booking $booking,
        ?SupplierBooking $supplierBooking = null,
        ?SupplierQuote $quote = null,
        string $reasonCode = '',
        array $context = [],
        bool $force = false
    ): bool {
        if ($booking->object_model !== 'tsa_supplier_flight') {
            return false;
        }

        $alreadySentAt = method_exists($booking, 'getMeta')
            ? $booking->getMeta('tsa_manual_review_notification_sent_at')
            : null;

        if (!$force && $alreadySentAt) {
            return false;
        }

        $supplierBooking = $supplierBooking ?: SupplierBooking::where('booking_id', $booking->id)->latest('id')->first();
        $quote = $quote ?: ($supplierBooking ? SupplierQuote::find($supplierBooking->quote_id) : null);

        $customerEmail = trim((string) $booking->email);
        $adminEmail = $this->adminEmail();

        $sent = false;

        try {
            if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($customerEmail)->send(
                    new SupplierFlightManualReviewEmail(
                        $booking,
                        $supplierBooking,
                        $quote,
                        'customer',
                        $reasonCode,
                        $context
                    )
                );
                $sent = true;
            }

            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::to($adminEmail)->send(
                    new SupplierFlightManualReviewEmail(
                        $booking,
                        $supplierBooking,
                        $quote,
                        'admin',
                        $reasonCode,
                        $context
                    )
                );
                $sent = true;
            }

            if ($sent && !$force && method_exists($booking, 'addMeta')) {
                $booking->addMeta('tsa_manual_review_notification_sent_at', now()->toDateTimeString());
                $booking->addMeta('tsa_manual_review_notification_reason', $reasonCode);
                $booking->save();
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('TSA supplier flight manual review notification failed', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
                'supplier_booking_id' => optional($supplierBooking)->id,
                'reason_code' => $reasonCode,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function adminEmail(): ?string
    {
        foreach ([
            env('TSA_ADMIN_EMAIL'),
            env('BOOKING_ADMIN_EMAIL'),
            env('SYSTEM_ADMIN_EMAIL'),
            env('ADMIN_EMAIL'),
            setting_item('admin_email'),
            config('mail.from.address'),
        ] as $email) {
            $email = trim((string) $email);
            if ($email) {
                return $email;
            }
        }

        return null;
    }
}
