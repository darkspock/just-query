<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\QueryBuilder\Condition\JsonContains;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonContains} for MySQL.
 *
 * Generates: `JSON_CONTAINS(column, value[, path])`
 *
 * @implements ExpressionBuilderInterface<JsonContains>
 */
final class JsonContainsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);

        $value = $expression->value;
        if (!$value instanceof ExpressionInterface) {
            $value = new JsonValue($value);
        }

        /** @phpstan-ignore argument.type */
        $valueSql = $this->queryBuilder->buildExpression($value, $params);

        if ($expression->path !== null) {
            return "JSON_CONTAINS($column, $valueSql, " . $this->queryBuilder->getQuoter()->quoteValue($expression->path) . ')';
        }

        return "JSON_CONTAINS($column, $valueSql)";
    }
}
