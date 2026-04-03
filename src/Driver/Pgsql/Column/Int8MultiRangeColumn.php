<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Schema\Column\ColumnInterface;

final class Int8MultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT8MULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new Int8RangeColumn();
    }
}
