<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression;

use DateTimeImmutable;
use JustQuery\Expression\ExpressionInterface;

final class TsRangeValue implements ExpressionInterface
{
    public function __construct(
        public readonly ?DateTimeImmutable $lower = null,
        public readonly ?DateTimeImmutable $upper = null,
        public readonly bool $includeLower = true,
        public readonly bool $includeUpper = true,
    ) {}
}
