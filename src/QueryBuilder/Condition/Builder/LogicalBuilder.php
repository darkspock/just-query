<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use InvalidArgumentException;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\AndX;
use JustQuery\QueryBuilder\Condition\OrX;
use JustQuery\QueryBuilder\QueryBuilderInterface;

use function count;
use function implode;
use function is_array;
use function reset;

/**
 * Build an object of {@see AndX} or {@see OrX} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<AndX|OrX>
 */
final class LogicalBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Build SQL for {@see AndX} or {@see OrX}.
     *
     * @param AndX|OrX $expression
     * @param array<int|string, mixed> $params
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $parts = $this->buildExpressions($expression->expressions, $params);

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return (string) reset($parts);
        }

        $operator = match ($expression::class) {
            AndX::class => 'AND',
            OrX::class => 'OR',
        };

        return '(' . implode(") $operator (", $parts) . ')';
    }

    /**
     * @param array<int, mixed> $expressions
     * @param array<int|string, mixed> $params
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     * @psalm-return list<scalar>
     */
    private function buildExpressions(array $expressions, array &$params = []): array // @phpstan-ignore missingType.iterableValue
    {
        $parts = [];

        foreach ($expressions as $conditionValue) {
            if (is_array($conditionValue)) {
                $conditionValue = $this->queryBuilder->buildCondition($conditionValue, $params); // @phpstan-ignore argument.type, argument.type
            }

            if ($conditionValue instanceof ExpressionInterface) {
                $conditionValue = $this->queryBuilder->buildExpression($conditionValue, $params); // @phpstan-ignore argument.type
            }

            if ($conditionValue !== '') {
                $parts[] = $conditionValue;
            }
        }

        return $parts;
    }
}
