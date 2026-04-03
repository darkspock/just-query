<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\TsRangeValue;
use JustQuery\Schema\Column\DateTimeColumn;

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
