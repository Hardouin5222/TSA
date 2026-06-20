<?php

namespace Modules\Flight\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Flight\Events\SupplierPaymentConfirmed;
use Modules\Flight\Models\SupplierBooking;
use Modules\Flight\Models\SupplierOperationLog;
use Modules\Flight\Models\SupplierQuote;
use Modules\Flight\Services\TsaSupplierBridgeClient;

class ProcessSupplierTicketing implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 120;

    protected TsaSupplierBridgeClient $bridgeClient;

    public function __construct(TsaSupplierBridgeClient $bridgeClient)
    {
        $this->bridgeClient = $bridgeClient;
    }

    public function handle(SupplierPaymentConfirmed $event): void
    {
        $booking = Booking::find($event->bookingId);
        if (!$booking || $booking->object_model !== 'tsa_supplier_flight') {
            return;
        }

        $supplierBooking = SupplierBooking::where('booking_id', $booking->id)->first();
        if (!$supplierBooking) {
            $this->log($booking, null, 'book', 'failed', 'SUPPLIER_BOOKING_ROW_NOT_FOUND', [], []);
            return;
        }

        if (in_array($supplierBooking->fulfillment_status, ['ticket_issued', 'booking_confirmed'], true)) {
            return;
        }

        $quote = SupplierQuote::find($supplierBooking->quote_id);
        if (!$quote || $quote->isExpired()) {
            $supplierBooking->fulfillment_status = 'manual_review_required';
            $supplierBooking->manual_review_required = true;
            $supplierBooking->save();
            $booking->addMeta('tsa_fulfillment_status', 'manual_review_required');
            $this->log($booking, $quote, 'book', 'failed', 'QUOTE_EXPIRED', [], []);
            return;
        }

        $payload = [
            'quote_id' => $quote->quote_uuid,
            'booking_reference' => $booking->code,
            'payment_reference' => $event->paymentPayload['payment_id'] ?? $event->paymentPayload['merchant_oid'] ?? null,
            'selected_fare_id' => $quote->selected_fare_id,
            'travellers' => data_get($supplierBooking->snapshot_json, 'travellers', []),
            'contact' => data_get($supplierBooking->snapshot_json, 'contact', []),
            'billing' => data_get($supplierBooking->snapshot_json, 'billing', []),
            'offer_snapshot' => data_get($supplierBooking->snapshot_json, 'offer', []),
            'supplier_context' => data_get($supplierBooking->snapshot_json, 'offer.supplier_context', []),
        ];

        $started = microtime(true);
        $correlationId = (string) Str::uuid();

        try {
            $supplierBooking->fulfillment_status = 'ticketing_in_progress';
            $supplierBooking->save();

            $response = $this->bridgeClient->book($payload);
            $duration = (int) ((microtime(true) - $started) * 1000);

            DB::transaction(function () use ($booking, $supplierBooking, $quote, $payload, $response, $duration, $correlationId) {
                $supplierBooking->supplier_booking_reference = $response['supplier_booking_reference'] ?? null;
                $supplierBooking->pnr = $response['pnr'] ?? null;
                $supplierBooking->ticket_numbers_json = $response['ticket_numbers'] ?? [];
                $supplierBooking->payment_status = 'payment_paid';
                $supplierBooking->fulfillment_status = $response['fulfillment_status'] ?? 'booking_confirmed';
                $supplierBooking->manual_review_required = (bool) ($response['manual_action_required'] ?? false);
                $supplierBooking->snapshot_json = array_merge($supplierBooking->snapshot_json ?: [], [
                    'supplier_book_response' => $response,
                ]);
                $supplierBooking->save();

                $booking->addMeta('tsa_supplier_booking_reference', $supplierBooking->supplier_booking_reference);
                $booking->addMeta('tsa_pnr', $supplierBooking->pnr);
                $booking->addMeta('tsa_ticket_numbers', $supplierBooking->ticket_numbers_json ?: []);
                $booking->addMeta('tsa_fulfillment_status', $supplierBooking->fulfillment_status);

                if (in_array($supplierBooking->fulfillment_status, ['ticket_issued', 'booking_confirmed'], true)) {
                    $booking->status = Booking::CONFIRMED;
                }
                if ($supplierBooking->manual_review_required) {
                    $booking->status = Booking::PROCESSING;
                }
                $booking->save();

                $this->log($booking, $quote, 'book', 'success', null, $payload, $response, $duration, $correlationId);
            });
        } catch (\Throwable $e) {
            report($e);
            $duration = (int) ((microtime(true) - $started) * 1000);
            $supplierBooking->fulfillment_status = 'manual_review_required';
            $supplierBooking->manual_review_required = true;
            $supplierBooking->save();
            $booking->status = Booking::PROCESSING;
            $booking->addMeta('tsa_fulfillment_status', 'manual_review_required');
            $booking->save();
            $this->log($booking, $quote, 'book', 'failed', 'SUPPLIER_BOOKING_FAILED', $payload, ['error' => $e->getMessage()], $duration, $correlationId);
            throw $e;
        }
    }

    protected function log(Booking $booking, ?SupplierQuote $quote, string $operation, string $status, ?string $errorCode, array $request = [], array $response = [], ?int $durationMs = null, ?string $correlationId = null): void
    {
        SupplierOperationLog::create([
            'booking_id' => $booking->id,
            'quote_id' => $quote ? $quote->id : null,
            'quote_uuid' => $quote ? $quote->quote_uuid : null,
            'supplier_code' => $quote ? $quote->supplier_code : null,
            'operation' => $operation,
            'status' => $status,
            'normalized_error_code' => $errorCode,
            'request_json' => $request,
            'response_json' => $response,
            'duration_ms' => $durationMs,
            'correlation_id' => $correlationId,
        ]);
    }
}
