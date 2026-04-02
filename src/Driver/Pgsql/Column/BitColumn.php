<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Constant\ColumnType;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Schema\Column\AbstractColumn;

use function bindec;
use function is_string;

final class BitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return BitColumnInternal::dbTypecast($value, $this->getSize());
    }

    public function phpTypecast(mixed $value): ?int
    {
        /** @var int|string|null $value */
        if (is_string($value)) {
            /** @var int */
            return bindec($value);
        }

        return $value;
    }
}
