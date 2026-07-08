<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Lib\ZarinpalClient;

/** Fake transport so tests never touch the real Zarinpal sandbox over the network. */
final class FakeZarinpalClient extends ZarinpalClient
{
    /** @var array<int, array<string, mixed>> */
    public array $responses = [];

    /** @var array<int, array{url: string, payload: array<string, mixed>}> */
    public array $calls = [];

    protected function httpPost(string $url, array $payload): array
    {
        $this->calls[] = ['url' => $url, 'payload' => $payload];

        return array_shift($this->responses) ?? [];
    }
}
