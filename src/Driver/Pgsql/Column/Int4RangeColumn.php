<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\Int4RangeValue;

final class Int4RangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT4RANGE;

    protected function getBoundColumn(): IntegerColumn
    {
        return RangeBoundColumnFactory::int4();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): Int4RangeValue
    {
        $column = $this->getBoundColumn();
        return new Int4RangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
