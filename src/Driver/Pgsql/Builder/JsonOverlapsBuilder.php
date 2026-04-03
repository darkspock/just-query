<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\Expression\Value\ArrayValue;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\QueryBuilder\Condition\JsonOverlaps;
use JustQuery\QueryBuilder\QueryBuilderInterface;

use function preg_match;

/**
 * Builds expressions for {@see JsonOverlaps} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonOverlaps>
 */
final class JsonOverlapsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Build SQL for {@see JsonOverlaps}.
     *
     * @param JsonOverlaps $expression The {@see JsonOverlaps} to be built.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if ($values instanceof JsonValue) {
            /** @psalm-suppress MixedArgument */
            $values = new ArrayValue($values->value); // @phpstan-ignore argument.type
        } elseif (!$values instanceof ExpressionInterface) {
            $values = new ArrayValue($values); // @phpstan-ignore argument.type
        }

        $values = $this->queryBuilder->buildExpression($values, $params); // @phpstan-ignore argument.type

        if (preg_match('/::\w+\[]$/', $values, $matches) === 1) {
            $typeHint = $matches[0];

            return "ARRAY(SELECT jsonb_array_elements_text($column::jsonb))$typeHint && $values";
        }

        return "ARRAY(SELECT jsonb_array_elements_text($column::jsonb)) && $values::text[]";
    }
}
