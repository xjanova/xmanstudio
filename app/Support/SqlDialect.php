<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cross-driver SQL fragment builders.
 *
 * Production runs MySQL while local development runs SQLite (see CLAUDE.md), so
 * raw SQL handed to selectRaw/whereRaw/orderByRaw must not depend on MySQL-only
 * functions. NOW(), DATE_FORMAT(), FIELD(), TIMESTAMPDIFF(), JSON_LENGTH(),
 * STDDEV() and GROUP_CONCAT(... SEPARATOR ...) all abort on SQLite with
 * "no such function", which takes the whole page down in dev.
 *
 * Each helper emits exactly the MySQL it replaced, so production behaviour is
 * unchanged — the SQLite branch is the one being added.
 *
 * Portable without help (verified on SQLite 3.40 / MySQL 8.4): DATE(), ABS(),
 * COALESCE(), IFNULL(), SUBSTRING(), CASE/WHEN and the plain aggregates.
 */
class SqlDialect
{
    public static function isSqlite(?string $connection = null): bool
    {
        return DB::connection($connection)->getDriverName() === 'sqlite';
    }

    /**
     * Portable replacement for MySQL's FIELD(col, v1, v2, ...), used to impose a
     * custom sort order. Returns 1..N for the listed values and 0 for anything
     * else — including NULL — which is what FIELD() does, so unlisted values
     * keep sorting first under ASC.
     *
     * The values become bindings, so pass the same array to the query:
     *     ->orderByRaw(SqlDialect::field('priority', $order), $order)
     *
     * @param  list<string>  $values
     */
    public static function field(string $column, array $values): string
    {
        if ($values === []) {
            throw new InvalidArgumentException('SqlDialect::field() needs at least one value.');
        }

        $sql = "CASE {$column}";

        foreach (array_keys(array_values($values)) as $i) {
            $sql .= ' WHEN ? THEN ' . ($i + 1);
        }

        return $sql . ' ELSE 0 END';
    }

    /**
     * Format a datetime column. $format is a MySQL DATE_FORMAT string; only the
     * specifiers shared with SQLite's strftime are safe: %Y %m %d %H %M %S.
     */
    public static function dateFormat(string $column, string $format, ?string $connection = null): string
    {
        $format = static::literal($format);

        return static::isSqlite($connection)
            ? "strftime('{$format}', {$column})"
            : "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Whole hours from $start to $end, truncated toward zero the way MySQL's
     * TIMESTAMPDIFF(HOUR, ...) truncates.
     */
    public static function hoursBetween(string $start, string $end, ?string $connection = null): string
    {
        return static::isSqlite($connection)
            ? "CAST((julianday({$end}) - julianday({$start})) * 24 AS INTEGER)"
            : "TIMESTAMPDIFF(HOUR, {$start}, {$end})";
    }

    /** Number of elements in a JSON array column. */
    public static function jsonLength(string $column, ?string $connection = null): string
    {
        return static::isSqlite($connection)
            ? "json_array_length({$column})"
            : "JSON_LENGTH({$column})";
    }

    /**
     * Population variance — the square of MySQL's STDDEV().
     *
     * Variance rather than standard deviation because SQLite has neither
     * STDDEV() nor SQRT() unless it was compiled with SQLITE_ENABLE_MATH_
     * FUNCTIONS, which the PHP-bundled build is not. Callers take the square
     * root in PHP; clamp at zero first, since float error can leave a zero
     * variance marginally negative.
     */
    public static function variancePop(string $expression, ?string $connection = null): string
    {
        if (! static::isSqlite($connection)) {
            return "VAR_POP({$expression})";
        }

        return "(AVG(({$expression}) * ({$expression})) - AVG({$expression}) * AVG({$expression}))";
    }

    /** Concatenate an expression across a group with an explicit separator. */
    public static function groupConcat(string $expression, string $separator, ?string $connection = null): string
    {
        $separator = static::literal($separator);

        return static::isSqlite($connection)
            ? "GROUP_CONCAT({$expression}, '{$separator}')"
            : "GROUP_CONCAT({$expression} SEPARATOR '{$separator}')";
    }

    /**
     * Guard the few values that have to be inlined as SQL string literals.
     * Callers pass compile-time constants; this only exists so that a future
     * caller cannot quietly turn one into an injection point.
     */
    protected static function literal(string $value): string
    {
        if (preg_match('/[\'"\\\\]/', $value)) {
            throw new InvalidArgumentException('SQL literal may not contain quotes or backslashes.');
        }

        return $value;
    }
}
