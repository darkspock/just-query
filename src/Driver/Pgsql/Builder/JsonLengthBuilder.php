<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\JsonLength;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonLength} for PostgreSQL.
 *
 * Generates: `jsonb_array_length(column::jsonb) operator value`
 *
 * @implements ExpressionBuilderInterface<JsonLength>
 */
final class JsonLengthBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);

        if ($expression->path !== null) {
            $pathParts = $this->parseJsonPath($expression->path);
            return "jsonb_array_length(($column$pathParts)::jsonb) {$expression->operator} {$expression->length}";
        }

        return "jsonb_array_length($column::jsonb) {$expression->operator} {$expression->length}";
    }

    private function parseJsonPath(string $path): string
    {
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
