<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Constant\PgsqlColumnType;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

final class DateMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::DATEMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new DateRangeColumn();
    }
}
