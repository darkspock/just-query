<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\Int4RangeValue;
use JustQuery\Schema\Column\ColumnInterface;

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
