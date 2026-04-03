<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Constant\ColumnType;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Schema\Column\AbstractColumn;

final class BigBitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return BitColumnInternal::dbTypecast($value, $this->getSize());
    }

    public function phpTypecast(mixed $value): ?string
    {
        /** @var string|null $value */
        return $value;
    }
}
