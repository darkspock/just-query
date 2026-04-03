<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\TsRangeValue;
use JustQuery\Schema\Column\ColumnInterface;

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
