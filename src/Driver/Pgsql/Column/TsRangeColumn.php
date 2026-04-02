<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Constant\PgsqlColumnType;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsRangeValue;
use FastPHP\QueryBuilder\Schema\Column\DateTimeColumn;

final class TsRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSRANGE;

    protected function getBoundColumn(): DateTimeColumn
    {
        return RangeBoundColumnFactory::ts();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): TsRangeValue
    {
        $column = $this->getBoundColumn();
        return new TsRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
