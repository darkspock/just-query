<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Schema\Column\ColumnInterface;

final class TsMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new TsRangeColumn();
    }
}
