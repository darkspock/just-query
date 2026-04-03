<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql\Builder;

use JustQuery\Exception\Exception;
use InvalidArgumentException;
use JustQuery\Exception\InvalidConfigException;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\QueryBuilder\Condition\JsonOverlaps;
use JustQuery\QueryBuilder\QueryBuilderInterface;

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

        /** @phpstan-ignore argument.type */
        $values = $this->queryBuilder->buildExpression($values, $params);

        return "JSON_OVERLAPS($column, $values)";
    }
}
