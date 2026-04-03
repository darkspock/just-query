<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\DateRangeValue;
use JustQuery\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see DateRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<DateRangeValue>
 */
final class DateRangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::date();
    }
}
