<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Minimal Zarinpal Payment Gateway (v4 REST API) client - no SDK dependency.
 * The HTTP transport is a protected, overridable method so tests can supply
 * a fake transport instead of hitting the real sandbox over the network.
 */
class ZarinpalClient
{
    private string $merchantId;

    private bool $sandbox;

    public function __construct(?string $merchantId = null, ?bool $sandbox = null)
    {
        $this->merchantId = $merchantId ?? ($_ENV['ZARINPAL_MERCHANT_ID'] ?? '');
        $this->sandbox = $sandbox ?? filter_var($_ENV['ZARINPAL_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    private function baseUrl(): string
    {
        return $this->sandbox ? 'https://sandbox.zarinpal.com' : 'https://payment.zarinpal.com';
    }

    public function gatewayUrl(string $authority): string
    {
        return $this->baseUrl() . '/pg/StartPay/' . $authority;
    }

    /** @return array{authority: string, gateway_url: string} */
    public function request(int $amountRial, string $description, string $callbackUrl, ?string $mobile = null, ?string $email = null): array
    {
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amountRial,
            'description' => $description,
            'callback_url' => $callbackUrl,
        ];

        if ($mobile !== null || $email !== null) {
            $payload['metadata'] = array_filter(['mobile' => $mobile, 'email' => $email]);
        }

        $response = $this->httpPost($this->baseUrl() . '/pg/v4/payment/request.json', $payload);

        $code = $response['data']['code'] ?? null;

        if ($code !== 100) {
            $message = $response['errors']['message'] ?? 'خطای نامشخص از درگاه پرداخت';
            throw new \RuntimeException('خطا در ایجاد تراکنش زرین‌پال: ' . $message);
        }

        $authority = $response['data']['authority'];

        return [
            'authority' => $authority,
            'gateway_url' => $this->baseUrl() . '/pg/StartPay/' . $authority,
        ];
    }

    /** @return array{ref_id: string, code: int} */
    public function verify(int $amountRial, string $authority): array
    {
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amountRial,
            'authority' => $authority,
        ];

        $response = $this->httpPost($this->baseUrl() . '/pg/v4/payment/verify.json', $payload);

        $code = $response['data']['code'] ?? null;

        // 100 = freshly verified, 101 = already verified before (still a success for idempotent retries).
        if ($code !== 100 && $code !== 101) {
            $message = $response['errors']['message'] ?? 'تایید پرداخت با خطا مواجه شد';
            throw new \RuntimeException('تراکنش زرین‌پال ناموفق بود: ' . $message);
        }

        return ['ref_id' => (string) $response['data']['ref_id'], 'code' => $code];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    protected function httpPost(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('ارتباط با درگاه پرداخت برقرار نشد: ' . $error);
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('پاسخ نامعتبر از درگاه پرداخت دریافت شد.');
        }

        return $decoded;
    }
}
