<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression;

use FastPHP\QueryBuilder\Expression\ExpressionInterface;

final class NumRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|float|null $lower = null,
        public readonly int|float|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
