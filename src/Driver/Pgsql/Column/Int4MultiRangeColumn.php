<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Schema\Column\ColumnInterface;

final class Int4MultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT4MULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new Int4RangeColumn();
    }
}
