<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition\Builder;

use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Exists;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotExists;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see Exists} or {@see NotExists} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Exists|NotExists>
 */
class ExistsBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Build SQL for {@see Exists} or {@see NotExists}.
     *
     * @param Exists|NotExists $expression
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            Exists::class => 'EXISTS',
            NotExists::class => 'NOT EXISTS',
        };

        $sql = $this->queryBuilder->buildExpression($expression->query, $params);

        return "$operator $sql";
    }
}
