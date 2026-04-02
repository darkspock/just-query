<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition\Builder;

use InvalidArgumentException;
use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Between;
use FastPHP\QueryBuilder\QueryBuilder\Condition\ConditionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Equals;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Exists;
use FastPHP\QueryBuilder\QueryBuilder\Condition\GreaterThan;
use FastPHP\QueryBuilder\QueryBuilder\Condition\GreaterThanOrEqual;
use FastPHP\QueryBuilder\QueryBuilder\Condition\In;
use FastPHP\QueryBuilder\QueryBuilder\Condition\LessThan;
use FastPHP\QueryBuilder\QueryBuilder\Condition\LessThanOrEqual;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Like;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Not;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotBetween;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotEquals;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotExists;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotIn;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotLike;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;

use function is_array;

/**
 * Build an object of {@see Not} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Not>
 */
class NotBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Build SQL for {@see Not}.
     *
     * @param Not $expression
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $condition = is_array($expression->condition)
            ? $this->queryBuilder->createConditionFromArray($expression->condition)
            : $expression->condition;

        if ($condition === null || $condition === '') {
            return '';
        }

        if ($condition instanceof ConditionInterface) {
            $negatedCondition = $this->createNegatedCondition($condition);
            if ($negatedCondition !== null) {
                return $this->queryBuilder->buildCondition($negatedCondition, $params);
            }
        }

        $sql = $this->queryBuilder->buildCondition($condition, $params);
        return $sql === '' ? '' : "NOT ($sql)";
    }

    protected function createNegatedCondition(ConditionInterface $condition): array|string|ExpressionInterface|null
    {
        return match ($condition::class) {
            LessThan::class => new GreaterThanOrEqual($condition->column, $condition->value),
            LessThanOrEqual::class => new GreaterThan($condition->column, $condition->value),
            GreaterThan::class => new LessThanOrEqual($condition->column, $condition->value),
            GreaterThanOrEqual::class => new LessThan($condition->column, $condition->value),
            In::class => new NotIn($condition->column, $condition->values),
            NotIn::class => new In($condition->column, $condition->values),
            Between::class => new NotBetween(
                $condition->column,
                $condition->intervalStart,
                $condition->intervalEnd,
            ),
            NotBetween::class => new Between(
                $condition->column,
                $condition->intervalStart,
                $condition->intervalEnd,
            ),
            Equals::class => new NotEquals($condition->column, $condition->value),
            NotEquals::class => new Equals($condition->column, $condition->value),
            Exists::class => new NotExists($condition->query),
            NotExists::class => new Exists($condition->query),
            Like::class => new NotLike(
                $condition->column,
                $condition->value,
                $condition->caseSensitive,
                $condition->escape,
                $condition->mode,
                $condition->conjunction,
            ),
            NotLike::class => new Like(
                $condition->column,
                $condition->value,
                $condition->caseSensitive,
                $condition->escape,
                $condition->mode,
                $condition->conjunction,
            ),
            Not::class => $condition->condition,
            default => null,
        };
    }
}
