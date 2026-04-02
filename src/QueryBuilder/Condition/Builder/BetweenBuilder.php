<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition\Builder;

use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Between;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotBetween;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

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
            ? $this->queryBuilder->buildExpression($expression->column, $params)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);

        $phName1 = $this->createPlaceholder($expression->intervalStart, $params);
        $phName2 = $this->createPlaceholder($expression->intervalEnd, $params);

        return "$column $operator $phName1 AND $phName2";
    }

    /**
     * Attaches `$value` to `$params` array and return placeholder.
     *
     * @throws NotSupportedException
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }
}
