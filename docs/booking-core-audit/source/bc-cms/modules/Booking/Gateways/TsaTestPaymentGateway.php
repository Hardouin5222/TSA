<?php

namespace Modules\Booking\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;

class TsaTestPaymentGateway extends BaseGateway
{
    public $name = 'TSA Test Payment';
    public $is_offline = false;

    public function isAvailable()
    {
        if (app()->environment('production')) {
            return false;
        }

        return parent::isAvailable();
    }

    public function process(Request $request, $booking, $service)
    {
        if (app()->environment('production')) {
            abort(403, 'TSA Test Payment is disabled in production.');
        }

        $service->beforePaymentProcess($booking, $this);

        $reference = 'TSA-PAY-' . now()->format('YmdHis') . '-' . $booking->id;
        $amount = (float) ($booking->pay_now ?: $booking->total);
        $currency = $booking->currency ?: setting_item('currency_main', 'USD');

        $payment = new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id ?: 'tsa_test';
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->status = 'completed';
        $payment->user_id = $booking->customer_id ?: null;
        $payment->logs = json_encode([
            'gateway' => 'tsa_test',
            'reference' => $reference,
            'message' => 'TSA test payment completed. No real money was charged.',
            'booking_code' => $booking->code,
            'amount' => $amount,
            'currency' => $currency,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payment->save();
        $payment->addMeta('tsa_test_payment_reference', $reference);
        $payment->addMeta('booking_code', $booking->code);

        if ($booking->paid < $booking->total) {
            $booking->paid = $booking->total;
        }

        $booking->status = Booking::PAID;
        $booking->payment_id = $payment->id;
        $booking->addMeta('tsa_test_payment_reference', $reference);
        $booking->save();

        try {
            event(new BookingCreatedEvent($booking));
        } catch (\Throwable $e) {
            Log::warning('TSA test payment booking event failed: ' . $e->getMessage());
        }

        $service->afterPaymentProcess($booking, $this);

        return response()->json([
            'url' => $booking->getDetailUrl()
        ])->send();
    }

    public function getOptionsConfigs()
    {
        return [
            [
                'type'  => 'checkbox',
                'id'    => 'enable',
                'label' => __('Enable TSA Test Payment?')
            ],
            [
                'type'  => 'input',
                'id'    => 'name',
                'label' => __('Custom Name'),
                'std'   => __('TSA Test Payment'),
                'multi_lang' => '1'
            ],
            [
                'type'  => 'editor',
                'id'    => 'html',
                'label' => __('Custom HTML Description'),
                'std'   => __('Test payment gateway for TSA supplier ticketing flow. Do not use for real payments.'),
                'multi_lang' => '1'
            ],
        ];
    }
}
