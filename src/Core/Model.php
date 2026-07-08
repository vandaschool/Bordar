<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use PDO;

abstract class Model
{
    protected static string $table;

    protected static string $primaryKey = 'id';

    /** Set to false on tables without a deleted_at column. */
    protected static bool $softDeletes = true;

    protected static function pdo(): PDO
    {
        return Database::connection();
    }

    public static function find(string $id): ?array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = :id';
        $sql .= static::$softDeletes ? ' AND `deleted_at` IS NULL' : '';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` = :value';
        $sql .= static::$softDeletes ? ' AND `deleted_at` IS NULL' : '';
        $sql .= ' LIMIT 1';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function where(string $column, mixed $value): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` = :value';
        $sql .= static::$softDeletes ? ' AND `deleted_at` IS NULL' : '';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['value' => $value]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '`';
        $sql .= static::$softDeletes ? ' WHERE `deleted_at` IS NULL' : '';

        return static::pdo()->query($sql)->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public static function insert(array $data): string
    {
        if (!isset($data[static::$primaryKey])) {
            $data[static::$primaryKey] = self::uuid();
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            static::$table,
            implode(', ', array_map(static fn (string $c) => "`{$c}`", $columns)),
            implode(', ', $placeholders)
        );

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute($data);

        return (string) $data[static::$primaryKey];
    }

    /** @param array<string, mixed> $data */
    public static function update(string $id, array $data): bool
    {
        $assignments = implode(', ', array_map(static fn (string $c) => "`{$c}` = :{$c}", array_keys($data)));

        $sql = 'UPDATE `' . static::$table . '` SET ' . $assignments . ' WHERE `' . static::$primaryKey . '` = :__id';

        $data['__id'] = $id;

        $stmt = static::pdo()->prepare($sql);

        return $stmt->execute($data);
    }

    public static function softDelete(string $id): bool
    {
        if (!static::$softDeletes) {
            throw new \LogicException(static::$table . ' does not support soft deletes');
        }

        $sql = 'UPDATE `' . static::$table . '` SET `deleted_at` = :now WHERE `' . static::$primaryKey . '` = :id';
        $stmt = static::pdo()->prepare($sql);

        return $stmt->execute(['now' => gmdate('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
