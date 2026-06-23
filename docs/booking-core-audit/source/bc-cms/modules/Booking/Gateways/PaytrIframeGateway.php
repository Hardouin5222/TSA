<?php

namespace Modules\Booking\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;

class PaytrIframeGateway extends BaseGateway
{
    public $name = 'PayTR iFrame';
    public $is_offline = false;

    public function process(Request $request, $booking, $service)
    {
        $service->beforePaymentProcess($booking, $this);

        $amount = (float) ($booking->pay_now ?: $booking->total);
        $currency = $booking->currency ?: setting_item('currency_main', 'USD');
        $merchantOid = $booking->code;

        $payment = $booking->payment ?: new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id ?: 'paytr_iframe';
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->status = 'processing';
        $payment->user_id = $booking->customer_id ?: null;
        $payment->logs = json_encode([
            'gateway' => 'paytr_iframe',
            'merchant_oid' => $merchantOid,
            'message' => 'PayTR iFrame payment initialized. Waiting for callback.',
            'booking_code' => $booking->code,
            'amount' => $amount,
            'currency' => $currency,
            'callback_url' => $this->getSupplierPaymentCallbackUrl(),
            'return_url' => $booking->getDetailUrl(),
            'token_ready' => false,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payment->save();

        $payment->addMeta('merchant_oid', $merchantOid);
        $payment->addMeta('booking_code', $booking->code);
        $payment->addMeta('callback_url', $this->getSupplierPaymentCallbackUrl());

        $booking->gateway = $this->id ?: 'paytr_iframe';
        $booking->payment_id = $payment->id;
        $booking->status = Booking::PROCESSING;
        $booking->addMeta('paytr_merchant_oid', $merchantOid);
        $booking->addMeta('tsa_fulfillment_status', 'payment_pending');
        $booking->save();

        try {
            event(new BookingCreatedEvent($booking));
        } catch (\Throwable $e) {
            Log::warning('PayTR iFrame booking event failed: ' . $e->getMessage());
        }

        $service->afterPaymentProcess($booking, $this);

        return response()->json([
            'url' => $booking->getDetailUrl()
        ])->send();
    }

    protected function getSupplierPaymentCallbackUrl(): string
    {
        return url('/flight/supplier/webhooks/payment/paytr_iframe');
    }

    public function getOptionsConfigs()
    {
        return [
            [
                'type'  => 'checkbox',
                'id'    => 'enable',
                'label' => __('Enable PayTR iFrame?')
            ],
            [
                'type'  => 'input',
                'id'    => 'name',
                'label' => __('Custom Name'),
                'std'   => __('PayTR iFrame'),
                'multi_lang' => '1'
            ],
            [
                'type'  => 'input',
                'id'    => 'merchant_id',
                'label' => __('PayTR Merchant ID'),
            ],
            [
                'type'  => 'input',
                'id'    => 'merchant_key',
                'label' => __('PayTR Merchant Key'),
            ],
            [
                'type'  => 'input',
                'id'    => 'merchant_salt',
                'label' => __('PayTR Merchant Salt'),
            ],
            [
                'type'  => 'checkbox',
                'id'    => 'test_mode',
                'label' => __('PayTR Test Mode?')
            ],
            [
                'type'  => 'editor',
                'id'    => 'html',
                'label' => __('Custom HTML Description'),
                'std'   => __('Secure card payment via PayTR iFrame. The booking will wait for PayTR callback before ticketing.'),
                'multi_lang' => '1'
            ],
        ];
    }
}
