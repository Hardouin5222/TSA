<?php

namespace Modules\Flight\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierPaymentConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $bookingId;
    public string $paymentProvider;
    public array $paymentPayload;

    public function __construct(int $bookingId, string $paymentProvider, array $paymentPayload = [])
    {
        $this->bookingId = $bookingId;
        $this->paymentProvider = $paymentProvider;
        $this->paymentPayload = $paymentPayload;
    }
}
