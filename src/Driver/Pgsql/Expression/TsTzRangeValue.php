<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression;

use DateTimeImmutable;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;

final class TsTzRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?DateTimeImmutable $lower = null,
        public readonly ?DateTimeImmutable $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
