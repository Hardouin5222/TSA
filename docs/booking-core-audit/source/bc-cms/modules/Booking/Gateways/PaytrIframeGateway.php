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

    public function isAvailable()
    {
        if (!parent::isAvailable()) {
            return false;
        }

        if (app()->environment('production') && !$this->hasMerchantCredentials()) {
            return false;
        }

        return true;
    }

    protected function hasMerchantCredentials(): bool
    {
        $merchantId = $this->getOption('merchant_id') ?: env('PAYTR_MERCHANT_ID');
        $merchantKey = $this->getOption('merchant_key') ?: env('PAYTR_MERCHANT_KEY');
        $merchantSalt = $this->getOption('merchant_salt') ?: env('PAYTR_MERCHANT_SALT');

        return !empty($merchantId) && !empty($merchantKey) && !empty($merchantSalt);
    }

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
            'payment_page_url' => null,
            'token_ready' => false,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payment->save();

        $paymentPageUrl = $this->getPaymentPageUrl($booking, $payment);

        $payment->addMeta('merchant_oid', $merchantOid);
        $payment->addMeta('booking_code', $booking->code);
        $payment->addMeta('callback_url', $this->getSupplierPaymentCallbackUrl());
        $payment->addMeta('payment_page_url', $paymentPageUrl);

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
            'url' => $paymentPageUrl
        ])->send();
    }

    public function confirmPayment(Request $request)
    {
        $bookingCode = $request->query('booking_code') ?: $request->query('code');
        $paymentCode = $request->query('pid');

        $booking = $bookingCode ? Booking::where('code', $bookingCode)->first() : null;

        if (!$booking || $booking->gateway !== ($this->id ?: 'paytr_iframe')) {
            return response($this->renderErrorPage('PayTR booking could not be found.'), 404);
        }

        $payment = $paymentCode
            ? Payment::where('code', $paymentCode)->where('booking_id', $booking->id)->first()
            : ($booking->payment_id ? Payment::find($booking->payment_id) : null);

        if (!$payment) {
            return response($this->renderErrorPage('PayTR payment record could not be found.'), 404);
        }

        [$ok, $token, $error, $payload, $rawResponse] = $this->requestIframeToken($request, $booking, $payment);

        $logs = [
            'gateway' => 'paytr_iframe',
            'merchant_oid' => $booking->code,
            'booking_code' => $booking->code,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'callback_url' => $this->getSupplierPaymentCallbackUrl(),
            'return_url' => $booking->getDetailUrl(),
            'payment_page_url' => $this->getPaymentPageUrl($booking, $payment),
            'token_ready' => $ok,
            'token_error' => $error,
            'paytr_response' => $rawResponse,
        ];

        $payment->logs = json_encode($logs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payment->save();

        if ($ok && $token) {
            $payment->addMeta('paytr_iframe_token', $token);
        }

        return response($this->renderPaymentPage($booking, $payment, $token, $error));
    }

    protected function requestIframeToken(Request $request, Booking $booking, Payment $payment): array
    {
        $merchantId = $this->getOption('merchant_id') ?: env('PAYTR_MERCHANT_ID');
        $merchantKey = $this->getOption('merchant_key') ?: env('PAYTR_MERCHANT_KEY');
        $merchantSalt = $this->getOption('merchant_salt') ?: env('PAYTR_MERCHANT_SALT');

        if (!$merchantId || !$merchantKey || !$merchantSalt) {
            return [false, null, 'PayTR merchant_id / merchant_key / merchant_salt settings are missing.', [], null];
        }

        $payload = $this->buildTokenPayload($request, $booking, $payment, $merchantId, $merchantKey, $merchantSalt);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.paytr.com/odeme/api/get-token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $raw = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [false, null, 'PAYTR IFRAME connection error: ' . $error, $payload, null];
        }

        curl_close($ch);

        $decoded = json_decode((string) $raw, true);

        if (is_array($decoded) && ($decoded['status'] ?? null) === 'success' && !empty($decoded['token'])) {
            return [true, (string) $decoded['token'], null, $payload, $decoded];
        }

        $reason = is_array($decoded)
            ? ($decoded['reason'] ?? 'Unknown PayTR token error.')
            : 'Invalid PayTR token response.';

        return [false, null, $reason, $payload, $decoded ?: $raw];
    }

    protected function buildTokenPayload(
        Request $request,
        Booking $booking,
        Payment $payment,
        string $merchantId,
        string $merchantKey,
        string $merchantSalt
    ): array {
        $amount = (float) $payment->amount;
        $paymentAmount = (string) $this->amountToMinorUnit($amount);
        $merchantOid = $booking->code;
        $email = $this->customerEmail($booking);
        $currency = $this->normalizeCurrency($payment->currency ?: $booking->currency);
        $testMode = $this->getOption('test_mode') ? '1' : '0';
        $noInstallment = (string) ($this->getOption('no_installment', '0') ?: '0');
        $maxInstallment = (string) ($this->getOption('max_installment', '0') ?: '0');
        $timeoutLimit = (string) ($this->getOption('timeout_limit', '30') ?: '30');

        $userBasket = base64_encode(json_encode([
            ['Flight booking ' . $booking->code, number_format($amount, 2, '.', ''), 1],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $userIp = $this->clientIp($request);

        $hashStr = $merchantId
            . $userIp
            . $merchantOid
            . $email
            . $paymentAmount
            . $userBasket
            . $noInstallment
            . $maxInstallment
            . $currency
            . $testMode;

        $paytrToken = base64_encode(hash_hmac(
            'sha256',
            $hashStr . $merchantSalt,
            $merchantKey,
            true
        ));

        return [
            'merchant_id' => $merchantId,
            'user_ip' => $userIp,
            'merchant_oid' => $merchantOid,
            'email' => $email,
            'payment_amount' => $paymentAmount,
            'paytr_token' => $paytrToken,
            'user_basket' => $userBasket,
            'debug_on' => $this->getOption('debug_on', '1') ? '1' : '0',
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $this->customerName($booking),
            'user_address' => $this->customerAddress($booking),
            'user_phone' => $this->customerPhone($booking),
            'merchant_ok_url' => $booking->getDetailUrl() . '?paytr_return=success',
            'merchant_fail_url' => $booking->getDetailUrl() . '?paytr_return=failed',
            'timeout_limit' => $timeoutLimit,
            'currency' => $currency,
            'test_mode' => $testMode,
        ];
    }

    protected function getPaymentPageUrl(Booking $booking, Payment $payment): string
    {
        return $this->getReturnUrl() . '?booking_code=' . urlencode($booking->code) . '&pid=' . urlencode($payment->code);
    }

    protected function getSupplierPaymentCallbackUrl(): string
    {
        return url('/flight/supplier/webhooks/payment/paytr_iframe');
    }

    protected function amountToMinorUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    protected function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper((string) ($currency ?: 'TRY'));

        return $currency === 'TL' ? 'TRY' : $currency;
    }

    protected function clientIp(Request $request): string
    {
        $forwarded = $request->headers->get('x-forwarded-for');

        if ($forwarded) {
            return trim(explode(',', $forwarded)[0]);
        }

        return $request->ip() ?: '127.0.0.1';
    }

    protected function customerEmail(Booking $booking): string
    {
        return (string) ($booking->email ?: $booking->customer_email ?: 'customer@example.com');
    }

    protected function customerName(Booking $booking): string
    {
        $name = trim(($booking->first_name ?? '') . ' ' . ($booking->last_name ?? ''));

        return $name ?: 'TSA Customer';
    }

    protected function customerAddress(Booking $booking): string
    {
        $parts = array_filter([
            $booking->address ?? null,
            $booking->address2 ?? null,
            $booking->city ?? null,
            $booking->state ?? null,
            $booking->country ?? null,
        ]);

        return mb_substr(implode(' ', $parts) ?: 'Address not provided', 0, 400);
    }

    protected function customerPhone(Booking $booking): string
    {
        return mb_substr((string) ($booking->phone ?: '0000000000'), 0, 20);
    }

    protected function renderPaymentPage(Booking $booking, Payment $payment, ?string $token, ?string $error): string
    {
        $title = e('PayTR iFrame Payment');
        $bookingCode = e($booking->code);
        $amount = e(number_format((float) $payment->amount, 2) . ' ' . $payment->currency);
        $detailUrl = e($booking->getDetailUrl());
        $callbackUrl = e($this->getSupplierPaymentCallbackUrl());

        if ($token) {
            $iframeSrc = e('https://www.paytr.com/odeme/guvenli/' . $token);
            $content = <<<HTML
                <div class="notice success">PayTR token created. Complete payment in the secure iFrame below.</div>
                <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                <iframe src="{$iframeSrc}" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:700px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;"></iframe>
                <script>iFrameResize({}, '#paytriframe');</script>
            HTML;
        } else {
            $safeError = e($error ?: 'PayTR token is not available yet.');
            $content = <<<HTML
                <div class="notice warn">
                    <strong>PayTR token could not be created.</strong><br>
                    {$safeError}<br><br>
                    This is safe in local/test mode. After merchant credentials are added, this page will display the live PayTR iFrame.
                </div>
            HTML;
        }

        return <<<HTML
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f5f7fb; color:#111827; }
        .wrap { max-width: 980px; margin: 40px auto; padding: 0 18px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 10px 25px rgba(15,23,42,.06); padding:24px; }
        h1 { margin:0 0 12px; font-size:28px; }
        .muted { color:#6b7280; line-height:1.5; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin:18px 0; }
        .box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px; }
        .label { font-size:12px; color:#6b7280; margin-bottom:4px; }
        .value { font-weight:700; word-break:break-word; }
        .notice { padding:14px 16px; border-radius:10px; margin:18px 0; line-height:1.5; }
        .success { background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46; }
        .warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
        a.btn { display:inline-block; margin-top:14px; background:#111827; color:#fff; padding:10px 14px; border-radius:9px; text-decoration:none; }
        @media (max-width:700px){ .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>{$title}</h1>
            <p class="muted">Card data is entered only inside PayTR's secure payment form. Ticketing starts after PayTR callback confirms payment.</p>

            <div class="grid">
                <div class="box">
                    <div class="label">Booking Code</div>
                    <div class="value">{$bookingCode}</div>
                </div>
                <div class="box">
                    <div class="label">Amount</div>
                    <div class="value">{$amount}</div>
                </div>
                <div class="box">
                    <div class="label">Payment Status</div>
                    <div class="value">{$payment->status}</div>
                </div>
                <div class="box">
                    <div class="label">Callback URL</div>
                    <div class="value">{$callbackUrl}</div>
                </div>
            </div>

            {$content}

            <a class="btn" href="{$detailUrl}">Back to booking detail</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function renderErrorPage(string $message): string
    {
        $message = e($message);

        return <<<HTML
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>PayTR Payment Error</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f5f7fb;padding:40px;">
    <div style="max-width:720px;margin:auto;background:white;padding:24px;border-radius:12px;border:1px solid #e5e7eb;">
        <h1>PayTR Payment Error</h1>
        <p>{$message}</p>
    </div>
</body>
</html>
HTML;
    }

    public function getOptionsConfigs()
    {
        return [
            ['type' => 'checkbox', 'id' => 'enable', 'label' => __('Enable PayTR iFrame?')],
            ['type' => 'input', 'id' => 'name', 'label' => __('Custom Name'), 'std' => __('PayTR iFrame'), 'multi_lang' => '1'],
            ['type' => 'input', 'id' => 'merchant_id', 'label' => __('PayTR Merchant ID')],
            ['type' => 'input', 'id' => 'merchant_key', 'label' => __('PayTR Merchant Key')],
            ['type' => 'input', 'id' => 'merchant_salt', 'label' => __('PayTR Merchant Salt')],
            ['type' => 'checkbox', 'id' => 'test_mode', 'label' => __('PayTR Test Mode?')],
            ['type' => 'checkbox', 'id' => 'debug_on', 'label' => __('PayTR Debug On?')],
            ['type' => 'input', 'id' => 'timeout_limit', 'label' => __('PayTR Timeout Limit'), 'std' => '30'],
            ['type' => 'input', 'id' => 'no_installment', 'label' => __('No Installment'), 'std' => '0'],
            ['type' => 'input', 'id' => 'max_installment', 'label' => __('Max Installment'), 'std' => '0'],
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
