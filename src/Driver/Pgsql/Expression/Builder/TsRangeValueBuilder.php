<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Driver\Pgsql\Column\RangeBoundColumnFactory;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsRangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see TsRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<TsRangeValue>
 */
final class TsRangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::ts();
    }
}
