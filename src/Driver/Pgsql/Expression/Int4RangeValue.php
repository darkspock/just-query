<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression;

use FastPHP\QueryBuilder\Expression\ExpressionInterface;

final class Int4RangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?int $lower = null,
        public readonly ?int $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
