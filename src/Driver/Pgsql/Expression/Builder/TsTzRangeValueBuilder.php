<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Driver\Pgsql\Column\RangeBoundColumnFactory;
use JustQuery\Driver\Pgsql\Expression\TsTzRangeValue;
use JustQuery\Schema\Column\ColumnInterface;

/**
 * Builds expressions for {@see TsTzRangeValue}.
 *
 * @extends AbstractRangeValueBuilder<TsTzRangeValue>
 */
final class TsTzRangeValueBuilder extends AbstractRangeValueBuilder
{
    protected function getBoundColumn(): ColumnInterface
    {
        return RangeBoundColumnFactory::tsTz();
    }
}
