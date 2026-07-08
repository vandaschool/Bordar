<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\DateConverter;
use PHPUnit\Framework\TestCase;

final class DateConverterTest extends TestCase
{
    public function test_utc_datetime_converts_to_jalali(): void
    {
        // 2026-01-01 00:00:00 UTC => Tehran +03:30 => 1404/10/11
        $this->assertSame('1404/10/11', DateConverter::toJalaliDate('2026-01-01 00:00:00'));
    }

    public function test_jalali_midnight_converts_to_utc_using_tehran_offset(): void
    {
        // 1404/10/11 00:00:00 in Asia/Tehran (UTC+03:30) => 2025-12-31 20:30:00 UTC
        $utc = DateConverter::fromJalali('1404/10/11', '00:00:00');

        $this->assertSame('2025-12-31 20:30:00', $utc);
    }

    public function test_from_jalali_and_to_jalali_agree_on_the_same_instant(): void
    {
        $utc = DateConverter::fromJalali('1404/10/11', '12:00:00');

        $this->assertSame('1404/10/11', DateConverter::toJalaliDate($utc));
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', DateConverter::toJalali(null));
        $this->assertSame('', DateConverter::toJalali(''));
    }
}
