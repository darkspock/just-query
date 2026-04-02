<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Driver\Pgsql\Column\RangeBoundColumnFactory;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\NumRangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see NumRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<NumRangeValue>
 */
final class NumRangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::num();
    }
}
