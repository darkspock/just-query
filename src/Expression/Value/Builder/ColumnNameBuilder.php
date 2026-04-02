<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Expression\Value\Builder;

use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\Value\ColumnName;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see ColumnName} expressions.
 *
 * This builder takes {@see ColumnName} expressions and converts them into properly quoted column names suitable for
 * inclusion in SQL statements using the database-specific quoting rules.
 *
 * @implements ExpressionBuilderInterface<ColumnName>
 */
final class ColumnNameBuilder implements ExpressionBuilderInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder->getQuoter()->quoteColumnName($expression->name);
    }
}
