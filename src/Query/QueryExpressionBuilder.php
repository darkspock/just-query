<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Query;

use FastPHP\QueryBuilder\Exception\Exception;
use InvalidArgumentException;
use FastPHP\QueryBuilder\Exception\InvalidConfigException;
use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;
use FastPHP\QueryBuilder\QueryBuilder\AbstractQueryBuilder;

/**
 * Used internally to build a {@see Query} object using unified {@see AbstractQueryBuilder}
 * expression building interface.
 *
 * @implements ExpressionBuilderInterface<QueryInterface>
 */
final class QueryExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * @param QueryInterface $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        [$sql, $params] = $this->queryBuilder->build($expression, $params);
        return "($sql)";
    }
}
