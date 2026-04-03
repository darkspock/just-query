<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Schema\Column\ColumnInterface;

final class TsTzMultiRangeColumn extends AbstractMultiRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSTZMULTIRANGE;

    protected function getRangeColumn(): ColumnInterface
    {
        return new TsTzRangeColumn();
    }
}
