<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Driver\Pgsql\Expression\MultiRangeValue;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see MultiRangeValue}.
 *
 * @implements ExpressionBuilderInterface<MultiRangeValue>
 */
final class MultiRangeValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $ranges = array_map(
            fn(string|ExpressionInterface $range): string => trim(
                $range instanceof ExpressionInterface
                    ? $this->queryBuilder->prepareValue($range)
                    : $range,
                '\'',
            ),
            $expression->ranges,
        );
        return '\'{' . implode(',', $ranges) . '}\'';
    }
}
