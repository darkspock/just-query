<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Builder;

use FastPHP\QueryBuilder\Expression\Value\ArrayValue;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Value\JsonValue;
use FastPHP\QueryBuilder\QueryBuilder\Condition\ArrayOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

use function preg_match;

/**
 * Builds expressions for {@see ArrayOverlaps} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<ArrayOverlaps>
 */
final class ArrayOverlapsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Build SQL for {@see ArrayOverlaps}.
     *
     * @param ArrayOverlaps $expression The {@see ArrayOverlaps} to be built.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if (!$values instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            $values = new ArrayValue($values);
        } elseif ($values instanceof JsonValue) {
            /** @psalm-suppress MixedArgument */
            /** @phpstan-ignore argument.type */
            $values = new ArrayValue($values->value);
        }

        /** @phpstan-ignore argument.type */
        $values = $this->queryBuilder->buildExpression($values, $params);

        if (preg_match('/::\w+\[]$/', $values, $matches) === 1) {
            $typeHint = $matches[0];

            return "$column$typeHint && $values";
        }

        return "$column::text[] && $values::text[]";
    }
}
