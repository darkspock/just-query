<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Constant\PgsqlColumnType;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\NumRangeValue;
use FastPHP\QueryBuilder\Schema\Column\DoubleColumn;

final class NumRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::NUMRANGE;

    protected function getBoundColumn(): DoubleColumn
    {
        return RangeBoundColumnFactory::num();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): NumRangeValue
    {
        $column = $this->getBoundColumn();
        return new NumRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
