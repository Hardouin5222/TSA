<?php

namespace Modules\Flight\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Flight\Events\SupplierPaymentConfirmed;
use Modules\Flight\Models\SupplierBooking;
use Modules\Flight\Models\SupplierOperationLog;

class SupplierWebhookController extends Controller
{
    public function payment(Request $request, string $provider)
    {
        if (!$this->verifySignature($request, $provider)) {
            SupplierOperationLog::create([
                'operation' => 'payment_webhook',
                'status' => 'rejected',
                'supplier_code' => $provider,
                'normalized_error_code' => 'WEBHOOK_SIGNATURE_INVALID',
                'request_json' => $request->all(),
            ]);
            abort(403, 'Invalid signature');
        }

        $bookingCode = $request->input('booking_code') ?: $request->input('merchant_oid') ?: $request->input('conversationId');
        $paymentStatus = $this->normalizePaymentStatus($provider, $request->all());

        $booking = Booking::where('code', $bookingCode)->first();
        if (!$booking || $booking->object_model !== 'tsa_supplier_flight') {
            SupplierOperationLog::create([
                'operation' => 'payment_webhook',
                'status' => 'failed',
                'supplier_code' => $provider,
                'normalized_error_code' => 'BOOKING_NOT_FOUND',
                'request_json' => $request->all(),
            ]);
            return response()->json(['ok' => true]);
        }

        $supplierBooking = SupplierBooking::where('booking_id', $booking->id)->first();

        if ($paymentStatus !== 'paid') {
            $booking->status = Booking::UNPAID;
            $booking->save();
            if ($supplierBooking) {
                $supplierBooking->payment_status = $paymentStatus;
                $supplierBooking->fulfillment_status = 'payment_failed';
                $supplierBooking->save();
            }
            return response()->json(['ok' => true]);
        }

        if ($supplierBooking && $supplierBooking->payment_status === 'paid') {
            return response()->json(['ok' => true, 'idempotent' => true]);
        }

        $booking->paid = $booking->total;
        $booking->status = Booking::PAID;
        $booking->save();

        if ($supplierBooking) {
            $supplierBooking->payment_status = 'paid';
            $supplierBooking->fulfillment_status = 'payment_paid_ticketing_queued';
            $supplierBooking->save();
        }

        event(new SupplierPaymentConfirmed($booking->id, $provider, $request->all()));

        return response()->json(['ok' => true]);
    }

    protected function verifySignature(Request $request, string $provider): bool
    {
        if (app()->environment(['local', 'testing']) || config('flight.disable_webhook_signature_check', false)) {
            return true;
        }

        // TODO: Implement provider-specific signature checks before live mode.
        // iyzico: verify conversationId/paymentId with provider API or signed callback data.
        // PayTR: verify hash using merchant key/salt.
        return false;
    }

    protected function normalizePaymentStatus(string $provider, array $payload): string
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['paymentStatus'] ?? $payload['payment_status'] ?? ''));
        if (in_array($status, ['success', 'successful', 'paid', 'approved'], true)) {
            return 'paid';
        }
        if (in_array($status, ['failed', 'failure', 'declined', 'cancelled'], true)) {
            return 'failed';
        }
        return 'pending';
    }
}
