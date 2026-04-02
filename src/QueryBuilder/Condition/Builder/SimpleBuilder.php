<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition\Builder;

use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Simple;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see Simple} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Simple>
 */
class SimpleBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Build SQL for {@see Simple}.
     *
     * @param Simple $expression
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->operator;
        $column = $expression->column;
        $value = $expression->value;

        $column = $column instanceof ExpressionInterface
            /** @phpstan-ignore argument.type */
            ? $this->queryBuilder->buildExpression($column, $params)
            : $this->queryBuilder->getQuoter()->quoteColumnName($column);

        if ($value === null) {
            return "$column $operator NULL";
        }

        if ($value instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            return "$column $operator {$this->queryBuilder->buildExpression($value, $params)}";
        }

        return "$column $operator {$this->queryBuilder->buildValue($value, $params)}";
    }
}
