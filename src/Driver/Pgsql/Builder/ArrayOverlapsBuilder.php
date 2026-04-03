<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\Expression\Value\ArrayValue;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\QueryBuilder\Condition\ArrayOverlaps;
use JustQuery\QueryBuilder\QueryBuilderInterface;

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
