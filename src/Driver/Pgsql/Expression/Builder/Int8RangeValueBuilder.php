<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\Int8RangeValue;
use JustQuery\Schema\Column\ColumnInterface;

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
