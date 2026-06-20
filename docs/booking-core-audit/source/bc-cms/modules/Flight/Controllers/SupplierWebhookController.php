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

        $bookingCode = $this->resolveBookingCode($request);
        $paymentStatus = $this->normalizePaymentStatus($provider, $request->all());

        if (!$bookingCode) {
            SupplierOperationLog::create([
                'operation' => 'payment_webhook',
                'status' => 'failed',
                'supplier_code' => $provider,
                'normalized_error_code' => 'BOOKING_CODE_MISSING',
                'request_json' => $request->all(),
            ]);

            return response()->json(['ok' => true]);
        }

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

        if (!$supplierBooking) {
            SupplierOperationLog::create([
                'booking_id' => $booking->id,
                'operation' => 'payment_webhook',
                'status' => 'failed',
                'supplier_code' => $provider,
                'normalized_error_code' => 'SUPPLIER_BOOKING_ROW_NOT_FOUND',
                'request_json' => $request->all(),
            ]);

            return response()->json(['ok' => true]);
        }

        if ($paymentStatus === 'failed') {
            $booking->status = Booking::UNPAID;
            $booking->save();

            $supplierBooking->payment_status = 'payment_failed';
            $supplierBooking->fulfillment_status = 'payment_failed';
            $supplierBooking->save();

            SupplierOperationLog::create([
                'booking_id' => $booking->id,
                'quote_id' => $supplierBooking->quote_id,
                'quote_uuid' => $supplierBooking->quote_uuid,
                'supplier_code' => $supplierBooking->supplier_code,
                'operation' => 'payment_webhook',
                'status' => 'failed',
                'normalized_error_code' => 'PAYMENT_FAILED',
                'request_json' => $request->all(),
            ]);

            return response()->json(['ok' => true]);
        }

        if ($paymentStatus !== 'paid') {
            SupplierOperationLog::create([
                'booking_id' => $booking->id,
                'quote_id' => $supplierBooking->quote_id,
                'quote_uuid' => $supplierBooking->quote_uuid,
                'supplier_code' => $supplierBooking->supplier_code,
                'operation' => 'payment_webhook',
                'status' => 'pending',
                'normalized_error_code' => null,
                'request_json' => $request->all(),
            ]);

            return response()->json(['ok' => true]);
        }

        if (in_array($supplierBooking->fulfillment_status, [
            'ticketing_in_progress',
            'booking_confirmed',
            'ticket_issued',
        ], true)) {
            return response()->json([
                'ok' => true,
                'idempotent' => true,
            ]);
        }

        $booking->paid = $booking->total;
        $booking->status = Booking::PAID;
        $booking->save();

        $supplierBooking->payment_status = 'payment_paid';
        $supplierBooking->fulfillment_status = 'payment_paid_ticketing_queued';
        $supplierBooking->manual_review_required = false;
        $supplierBooking->save();

        SupplierOperationLog::create([
            'booking_id' => $booking->id,
            'quote_id' => $supplierBooking->quote_id,
            'quote_uuid' => $supplierBooking->quote_uuid,
            'supplier_code' => $supplierBooking->supplier_code,
            'operation' => 'payment_webhook',
            'status' => 'success',
            'normalized_error_code' => null,
            'request_json' => $request->all(),
        ]);

        event(new SupplierPaymentConfirmed($booking->id, $provider, $request->all()));

        return response()->json(['ok' => true]);
    }

    protected function resolveBookingCode(Request $request): ?string
    {
        return $request->input('booking_code')
            ?: $request->input('bookingCode')
            ?: $request->input('booking_reference')
            ?: $request->input('merchant_oid')
            ?: $request->input('conversationId')
            ?: $request->input('conversation_id');
    }

    protected function verifySignature(Request $request, string $provider): bool
    {
        if (app()->environment(['local', 'testing']) || config('flight.disable_webhook_signature_check', false)) {
            return true;
        }

        // TODO: Live mode için provider bazlı imza kontrolü eklenecek.
        // iyzico: conversationId/paymentId ile provider API doğrulama veya signed callback data.
        // PayTR/Kuveyt Türk: merchant key/salt/hash doğrulama.
        return false;
    }

    protected function normalizePaymentStatus(string $provider, array $payload): string
    {
        $status = strtolower((string) (
            $payload['status']
            ?? $payload['paymentStatus']
            ?? $payload['payment_status']
            ?? ''
        ));

        if (in_array($status, ['success', 'successful', 'paid', 'approved', 'true', 'payment_paid'], true)) {
            return 'paid';
        }

        if (in_array($status, ['failed', 'failure', 'declined', 'cancelled', 'canceled', 'payment_failed'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
