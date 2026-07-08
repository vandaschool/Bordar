<?php

declare(strict_types=1);

namespace App\Lib;

use DateTimeImmutable;
use DateTimeZone;
use Morilog\Jalali\Jalalian;

/**
 * Single wrapper for all Jalali/Gregorian date conversion across the app.
 * DB & API layers always use UTC ISO strings; only this class converts to
 * Jalali for display, applying the viewer's timezone.
 */
final class DateConverter
{
    private const DEFAULT_TIMEZONE = 'Asia/Tehran';

    /** Convert a UTC DB datetime string to a display-ready Jalali string. */
    public static function toJalali(?string $utcDateTime, string $format = 'Y/m/d H:i', ?string $timezone = null): string
    {
        if ($utcDateTime === null || $utcDateTime === '') {
            return '';
        }

        $date = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
        $date = $date->setTimezone(new DateTimeZone($timezone ?? self::DEFAULT_TIMEZONE));

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function toJalaliDate(?string $utcDateTime, ?string $timezone = null): string
    {
        return self::toJalali($utcDateTime, 'Y/m/d', $timezone);
    }

    /** Convert a Jalali date string (Y/m/d) coming from a form back to a UTC DB datetime string. */
    public static function fromJalali(string $jalaliDate, string $time = '00:00:00', ?string $timezone = null): string
    {
        $gregorian = Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
        [$h, $m, $s] = array_pad(explode(':', $time), 3, '0');

        $local = new DateTimeImmutable(
            $gregorian->format('Y-m-d') . " {$h}:{$m}:{$s}",
            new DateTimeZone($timezone ?? self::DEFAULT_TIMEZONE)
        );

        return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function relativeJalali(?string $utcDateTime, ?string $timezone = null): string
    {
        if ($utcDateTime === null || $utcDateTime === '') {
            return '';
        }

        $date = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
        $date = $date->setTimezone(new DateTimeZone($timezone ?? self::DEFAULT_TIMEZONE));

        return Jalalian::fromDateTime($date)->ago();
    }
}
