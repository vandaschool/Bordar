<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Splits a .sql migration file into individual executable statements. Strips
 * full-line `--` comments first so a semicolon inside a comment (e.g. "status
 * is X; becomes Y") can't be mistaken for a statement terminator - a real bug
 * a naive `explode(';', $sql)` hit on the mentor_sessions migration.
 */
final class SqlSplitter
{
    /** @return array<int, string> */
    public static function statements(string $sql): array
    {
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $sql);

        return array_values(array_filter(array_map('trim', explode(';', $withoutComments))));
    }
}
