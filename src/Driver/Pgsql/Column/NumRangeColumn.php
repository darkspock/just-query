<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\NumRangeValue;
use JustQuery\Schema\Column\DoubleColumn;

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
