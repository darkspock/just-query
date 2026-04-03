<?php

declare(strict_types=1);

namespace JustQuery\Query;

use JustQuery\Exception\Exception;
use InvalidArgumentException;
use JustQuery\Exception\InvalidConfigException;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\QueryBuilderInterface;
use JustQuery\QueryBuilder\AbstractQueryBuilder;

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
     * @param array<int|string, mixed> $params
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @phpstan-ignore argument.type */
        [$sql, $params] = $this->queryBuilder->build($expression, $params);
        return "($sql)";
    }
}
