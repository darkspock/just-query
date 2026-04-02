<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Driver\Pgsql\Column\RangeBoundColumnFactory;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int4RangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see Int4RangeValue}.
 *
 * @extends AbstractRangeValueBuilder<Int4RangeValue>
 */
final class Int4RangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::int4();
    }
}
