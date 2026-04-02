<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Schema\Column;

use FastPHP\QueryBuilder\Constant\ColumnType;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;

/**
 * Represents the metadata for a boolean column.
 */
class BooleanColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BOOLEAN;

    public function dbTypecast(mixed $value): bool|ExpressionInterface|null
    {
        return match ($value) {
            true => true,
            false => false,
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (bool) $value,
        };
    }

    public function phpTypecast(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== "\0";
    }
}
