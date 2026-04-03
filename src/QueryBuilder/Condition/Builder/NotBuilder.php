<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use InvalidArgumentException;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\Between;
use JustQuery\QueryBuilder\Condition\ConditionInterface;
use JustQuery\QueryBuilder\Condition\Equals;
use JustQuery\QueryBuilder\Condition\Exists;
use JustQuery\QueryBuilder\Condition\GreaterThan;
use JustQuery\QueryBuilder\Condition\GreaterThanOrEqual;
use JustQuery\QueryBuilder\Condition\In;
use JustQuery\QueryBuilder\Condition\LessThan;
use JustQuery\QueryBuilder\Condition\LessThanOrEqual;
use JustQuery\QueryBuilder\Condition\Like;
use JustQuery\QueryBuilder\Condition\Not;
use JustQuery\QueryBuilder\Condition\NotBetween;
use JustQuery\QueryBuilder\Condition\NotEquals;
use JustQuery\QueryBuilder\Condition\NotExists;
use JustQuery\QueryBuilder\Condition\NotIn;
use JustQuery\QueryBuilder\Condition\NotLike;
use JustQuery\QueryBuilder\QueryBuilderInterface;

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
     * @param array<int|string, mixed> $params
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $condition = is_array($expression->condition)
            ? $this->queryBuilder->createConditionFromArray($expression->condition) // @phpstan-ignore argument.type
            : $expression->condition;

        if ($condition === null || $condition === '') {
            return '';
        }

        if ($condition instanceof ConditionInterface) {
            $negatedCondition = $this->createNegatedCondition($condition);
            if ($negatedCondition !== null) {
                return $this->queryBuilder->buildCondition($negatedCondition, $params); // @phpstan-ignore argument.type, argument.type
            }
        }

        $sql = $this->queryBuilder->buildCondition($condition, $params); // @phpstan-ignore argument.type
        return $sql === '' ? '' : "NOT ($sql)";
    }

    /**
     * @return array<int|string, mixed>|string|ExpressionInterface|null
     */
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
