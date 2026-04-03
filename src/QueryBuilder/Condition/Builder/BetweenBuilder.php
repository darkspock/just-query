<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\Between;
use JustQuery\QueryBuilder\Condition\NotBetween;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see Between} or {@see NotBetween} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Between|NotBetween>
 */
class BetweenBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Build SQL for {@see Between} or {@see NotBetween}.
     *
     * @param Between|NotBetween $expression
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            Between::class => 'BETWEEN',
            NotBetween::class => 'NOT BETWEEN',
        };
        $column = $expression->column instanceof ExpressionInterface
            /** @phpstan-ignore argument.type */
            ? $this->queryBuilder->buildExpression($expression->column, $params)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);

        $phName1 = $this->createPlaceholder($expression->intervalStart, $params);
        $phName2 = $this->createPlaceholder($expression->intervalEnd, $params);

        return "$column $operator $phName1 AND $phName2";
    }

    /**
     * Attaches `$value` to `$params` array and return placeholder.
     *
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            return $this->queryBuilder->buildExpression($value, $params);
        }

        /** @phpstan-ignore argument.type */
        return $this->queryBuilder->bindParam($value, $params);
    }
}
