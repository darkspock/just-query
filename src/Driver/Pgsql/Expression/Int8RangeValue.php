<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression;

use FastPHP\QueryBuilder\Expression\ExpressionInterface;

final class Int8RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly int|string|null $lower = null,
        public readonly int|string|null $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
