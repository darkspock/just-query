<?php

declare(strict_types=1);

namespace JustQuery\Schema\Column;

use JustQuery\Constant\ColumnType;
use JustQuery\Expression\ExpressionInterface;

use function is_int;

/**
 * Represents the metadata for a bit column.
 */
class BitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        if (is_int($value)) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (int) $value, // @phpstan-ignore cast.int
        };
    }

    public function phpTypecast(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value; // @phpstan-ignore cast.int
    }
}
