<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\DateRangeValue;
use JustQuery\Schema\Column\DateTimeColumn;

final class DateRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::DATERANGE;

    protected function getBoundColumn(): DateTimeColumn
    {
        return RangeBoundColumnFactory::date();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): DateRangeValue
    {
        $column = $this->getBoundColumn();
        return new DateRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
