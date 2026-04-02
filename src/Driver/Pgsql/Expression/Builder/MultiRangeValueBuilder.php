<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\MultiRangeValue;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

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
