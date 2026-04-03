<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\JsonLength;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonLength} for MySQL.
 *
 * Generates: `JSON_LENGTH(column[, path]) operator value`
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
            $pathSql = $this->queryBuilder->getQuoter()->quoteValue($expression->path);
            return "JSON_LENGTH($column, $pathSql) {$expression->operator} {$expression->length}";
        }

        return "JSON_LENGTH($column) {$expression->operator} {$expression->length}";
    }
}
