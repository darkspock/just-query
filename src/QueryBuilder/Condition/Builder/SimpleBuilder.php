<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\Simple;
use JustQuery\QueryBuilder\QueryBuilderInterface;

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
