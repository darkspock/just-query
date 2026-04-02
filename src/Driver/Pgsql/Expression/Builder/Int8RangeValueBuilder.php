<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Driver\Pgsql\Column\RangeBoundColumnFactory;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int8RangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see Int8RangeValue}.
 *
 * @extends AbstractRangeValueBuilder<Int8RangeValue>
 */
final class Int8RangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::int8();
    }
}
