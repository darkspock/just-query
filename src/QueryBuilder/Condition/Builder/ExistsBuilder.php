<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\Exists;
use JustQuery\QueryBuilder\Condition\NotExists;
use JustQuery\QueryBuilder\QueryBuilderInterface;

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
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            Exists::class => 'EXISTS',
            NotExists::class => 'NOT EXISTS',
        };

        /** @phpstan-ignore argument.type */
        $sql = $this->queryBuilder->buildExpression($expression->query, $params);

        return "$operator $sql";
    }
}
