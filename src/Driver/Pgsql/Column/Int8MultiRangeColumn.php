<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Constant\PgsqlColumnType;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

final class Int8MultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::INT8MULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new Int8RangeColumn();
    }
}
