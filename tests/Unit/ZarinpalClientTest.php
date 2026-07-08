<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\FakeZarinpalClient;

final class ZarinpalClientTest extends TestCase
{
    public function test_successful_request_returns_authority_and_gateway_url(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', true);
        $client->responses[] = ['data' => ['code' => 100, 'authority' => 'A00000000000000000000000000123456789'], 'errors' => []];

        $result = $client->request(500000000, 'Program fee', 'https://bordar.local/payment/callback');

        $this->assertSame('A00000000000000000000000000123456789', $result['authority']);
        $this->assertStringContainsString('sandbox.zarinpal.com/pg/StartPay/', $result['gateway_url']);
    }

    public function test_request_failure_throws_with_gateway_message(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', true);
        $client->responses[] = ['data' => [], 'errors' => ['code' => -9, 'message' => 'Invalid merchant']];

        $this->expectException(\RuntimeException::class);
        $client->request(500000000, 'Program fee', 'https://bordar.local/payment/callback');
    }

    public function test_verify_success_returns_ref_id(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', true);
        $client->responses[] = ['data' => ['code' => 100, 'ref_id' => 123456789], 'errors' => []];

        $result = $client->verify(500000000, 'AUTHORITY123');

        $this->assertSame('123456789', $result['ref_id']);
    }

    public function test_verify_code_101_is_treated_as_already_verified_success(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', true);
        $client->responses[] = ['data' => ['code' => 101, 'ref_id' => 999], 'errors' => []];

        $result = $client->verify(500000000, 'AUTHORITY123');

        $this->assertSame(101, $result['code']);
    }

    public function test_verify_failure_throws(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', true);
        $client->responses[] = ['data' => [], 'errors' => ['message' => 'Transaction failed']];

        $this->expectException(\RuntimeException::class);
        $client->verify(500000000, 'AUTHORITY123');
    }

    public function test_production_mode_uses_non_sandbox_host(): void
    {
        $client = new FakeZarinpalClient('test-merchant-id', false);

        $this->assertStringStartsWith('https://payment.zarinpal.com', $client->gatewayUrl('X'));
    }

    public function test_request_payload_sends_declared_amount_and_merchant(): void
    {
        $client = new FakeZarinpalClient('merchant-xyz', true);
        $client->responses[] = ['data' => ['code' => 100, 'authority' => 'AUTH'], 'errors' => []];

        $client->request(123456, 'desc', 'https://cb');

        $this->assertSame('merchant-xyz', $client->calls[0]['payload']['merchant_id']);
        $this->assertSame(123456, $client->calls[0]['payload']['amount']);
    }
}
