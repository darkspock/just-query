<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\AbstractCompare;
use JustQuery\QueryBuilder\Condition\Equals;
use JustQuery\QueryBuilder\Condition\GreaterThan;
use JustQuery\QueryBuilder\Condition\GreaterThanOrEqual;
use JustQuery\QueryBuilder\Condition\LessThan;
use JustQuery\QueryBuilder\Condition\LessThanOrEqual;
use JustQuery\QueryBuilder\Condition\NotEquals;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Build objects of {@see Equals}, {@see NotEquals}, {@see GreaterThan}, {@see GreaterThanOrEqual}, {@see LessThan},
 * or {@see LessThanOrEqual} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Equals|NotEquals|GreaterThan|GreaterThanOrEqual|LessThan|LessThanOrEqual>
 */
class CompareBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Build SQL for comparison conditions.
     *
     * @param Equals|GreaterThan|GreaterThanOrEqual|LessThan|LessThanOrEqual|NotEquals $expression
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $this->prepareColumn($expression->column, $params);
        $value = $this->prepareValue($expression->value, $params);

        $operator = $this->getOperator($expression);

        if ($value === null) {
            return match ($operator) {
                '=' => "$column IS NULL",
                '<>' => "$column IS NOT NULL",
                default => "$column $operator NULL",
            };
        }

        return "$column $operator $value";
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    private function prepareColumn(string|ExpressionInterface $column, array &$params): string
    {
        if ($column instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            return $this->queryBuilder->buildExpression($column, $params);
        }

        return $this->queryBuilder->getQuoter()->quoteColumnName($column);
    }

    /**
     * @param array<int|string, mixed> $params
     */
    private function prepareValue(mixed $value, array &$params): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->queryBuilder->buildValue($value, $params);
    }

    private function getOperator(AbstractCompare $expression): string
    {
        /** @phpstan-ignore match.unhandled */
        return match ($expression::class) {
            Equals::class => '=',
            NotEquals::class => '<>',
            GreaterThan::class => '>',
            GreaterThanOrEqual::class => '>=',
            LessThan::class => '<',
            LessThanOrEqual::class => '<=',
        };
    }
}
