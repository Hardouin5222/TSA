<?php

namespace Modules\Booking\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Models\Booking;

class TsaTestPaymentGateway extends BaseGateway
{
    public $name = 'TSA Test Payment';
    public $is_offline = false;

    public function process(Request $request, $booking, $service)
    {
        $service->beforePaymentProcess($booking, $this);

        if ($booking->paid < $booking->total) {
            $booking->paid = $booking->total;
        }

        $booking->status = Booking::PAID;
        $booking->payment_id = $booking->payment_id ?: null;
        $booking->addMeta('tsa_test_payment_reference', 'TSA-PAY-' . now()->format('YmdHis') . '-' . $booking->id);
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
