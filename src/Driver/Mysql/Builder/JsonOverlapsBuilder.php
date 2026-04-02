<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Mysql\Builder;

use FastPHP\QueryBuilder\Exception\Exception;
use InvalidArgumentException;
use FastPHP\QueryBuilder\Exception\InvalidConfigException;
use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Value\JsonValue;
use FastPHP\QueryBuilder\QueryBuilder\Condition\JsonOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonOverlaps} for MySQL Server.
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
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if (!$values instanceof ExpressionInterface) {
            $values = new JsonValue($values);
        }

        $values = $this->queryBuilder->buildExpression($values, $params);

        return "JSON_OVERLAPS($column, $values)";
    }
}
