<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\QueryBuilder\Condition\JsonContains;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonContains} for PostgreSQL.
 *
 * Generates: `column::jsonb @> value::jsonb` (or with path extraction).
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
            // Extract at path first, then check containment
            $pathParts = $this->parseJsonPath($expression->path);
            return "$column" . $pathParts . "::jsonb @> $valueSql::jsonb";
        }

        return "$column::jsonb @> $valueSql::jsonb";
    }

    /**
     * Converts '$.languages' to -> 'languages' PostgreSQL path syntax.
     */
    private function parseJsonPath(string $path): string
    {
        // Remove leading '$.' or '$'
        $path = ltrim($path, '$.');

        if ($path === '') {
            return '';
        }

        $parts = explode('.', $path);
        $sql = '';
        foreach ($parts as $part) {
            $sql .= '->' . $this->queryBuilder->getQuoter()->quoteValue($part);
        }

        return $sql;
    }
}
