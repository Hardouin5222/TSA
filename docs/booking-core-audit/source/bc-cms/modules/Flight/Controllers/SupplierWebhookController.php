<?php

namespace Modules\Flight\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
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
            $payment = $this->recordPaymentTransaction($booking, $provider, $request->all(), 'fail');

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
            $payment = $this->recordPaymentTransaction($booking, $provider, $request->all(), 'processing');

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

        $payment = $this->recordPaymentTransaction($booking, $provider, $request->all(), 'completed');

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

        event(new SupplierPaymentConfirmed($booking->id, $provider, array_merge($request->all(), [
            'gateway' => $provider,
            'booking_code' => $booking->code,
            'payment_id' => $payment->id,
            'payment_code' => $payment->code,
            'payment_reference' => $this->resolvePaymentReference($request->all()) ?: $payment->code,
            'payment_gateway' => $payment->payment_gateway,
            'payment_status' => $payment->status,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
        ])));

        return response()->json(['ok' => true]);
    }


    protected function recordPaymentTransaction(Booking $booking, string $provider, array $payload, string $status): Payment
    {
        $payment = $booking->payment ?: new Payment();

        $amount = $this->resolvePaymentAmount($booking, $payload);
        $currency = $this->resolvePaymentCurrency($booking, $payload);
        $externalReference = $this->resolvePaymentReference($payload);

        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $provider;
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->status = $status;
        $payment->user_id = $booking->customer_id ?: null;
        $payment->logs = json_encode([
            'gateway' => $provider,
            'external_reference' => $externalReference,
            'booking_code' => $booking->code,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'payload' => $payload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payment->save();

        $payment->addMeta('provider', $provider);
        if ($externalReference) {
            $payment->addMeta('provider_payment_reference', $externalReference);
        }
        $payment->addMeta('booking_code', $booking->code);

        $booking->payment_id = $payment->id;
        $booking->gateway = $provider;
        $booking->save();

        return $payment;
    }

    protected function resolvePaymentReference(array $payload): ?string
    {
        foreach ([
            'payment_reference',
            'payment_code',
            'payment_id',
            'paymentId',
            'merchant_oid',
            'conversationId',
            'conversation_id',
            'transaction_id',
            'transactionId',
            'token',
        ] as $key) {
            if (!empty($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    protected function resolvePaymentAmount(Booking $booking, array $payload): float
    {
        foreach (['amount', 'paid_amount', 'paidAmount', 'price', 'total'] as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        return (float) ($booking->pay_now ?: $booking->total);
    }

    protected function resolvePaymentCurrency(Booking $booking, array $payload): string
    {
        foreach (['currency', 'currency_code', 'currencyCode'] as $key) {
            if (!empty($payload[$key])) {
                return strtoupper((string) $payload[$key]);
            }
        }

        return $booking->currency ?: setting_item('currency_main', 'USD');
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
