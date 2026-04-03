<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\Int8RangeValue;

final class Int8RangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT8RANGE;

    protected function getBoundColumn(): BigIntColumn|IntegerColumn
    {
        return RangeBoundColumnFactory::int8();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): Int8RangeValue
    {
        $column = $this->getBoundColumn();
        return new Int8RangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
