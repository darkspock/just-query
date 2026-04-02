<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Driver\Pgsql\Column\RangeBoundColumnFactory;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\DateRangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

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
