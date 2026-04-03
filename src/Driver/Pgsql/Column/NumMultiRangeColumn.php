<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Schema\Column\ColumnInterface;

final class NumMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::NUMMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new NumRangeColumn();
    }
}
