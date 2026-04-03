<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Constant\PgsqlColumnType;
use JustQuery\Driver\Pgsql\Expression\TsTzRangeValue;
use JustQuery\Schema\Column\DateTimeColumn;

final class TsTzRangeColumn extends AbstractRangeColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::TSTZRANGE;

    protected function getBoundColumn(): DateTimeColumn
    {
        return RangeBoundColumnFactory::tsTz();
    }

    protected function createRangeValue(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): TsTzRangeValue
    {
        $column = $this->getBoundColumn();
        return new TsTzRangeValue(
            $column->phpTypecast($lower),
            $column->phpTypecast($upper),
            $includeLower,
            $includeUpper,
        );
    }
}
