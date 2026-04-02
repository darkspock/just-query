<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Constant\PgsqlColumnType;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsTzRangeValue;
use FastPHP\QueryBuilder\Schema\Column\DateTimeColumn;

final class TsTzRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSTZRANGE;

    protected function getBoundColumn(): DateTimeColumn
    {
        return RangeBoundColumnFactory::tsTz();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): TsTzRangeValue
    {
        $column = $this->getBoundColumn();
        return new TsTzRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
