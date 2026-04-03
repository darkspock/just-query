<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\NumRangeValue;
use JustQuery\Schema\Column\ColumnInterface;

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
