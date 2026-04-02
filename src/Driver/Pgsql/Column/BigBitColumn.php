<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Constant\ColumnType;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Schema\Column\AbstractColumn;

final class BigBitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return BitColumnInternal::dbTypecast($value, $this->getSize());
    }

    public function phpTypecast(mixed $value): ?string
    {
        /** @var string|null $value */
        return $value;
    }
}
