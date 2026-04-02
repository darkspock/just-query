<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

use InvalidArgumentException;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;

use function array_shift;
use function count;
use function is_array;
use function is_string;

/**
 * Condition that represents `NOT` operator (negation).
 */
final class Not implements ConditionInterface
{
    /**
     * @param array<int|string, mixed>|ExpressionInterface|string|null $condition The condition to negate.
     */
    public function __construct(
        public readonly ExpressionInterface|array|string|null $condition,
    ) {}

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 1.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self(self::validateCondition($operator, $operands));
    }

    /**
     * Validate the given condition have at least 1 condition and to be `array`, `string`, `null` or `ExpressionInterface`.
     *
     * @param array<int|string, mixed> $condition
     * @return array<int|string, mixed>|ExpressionInterface|string|null
     * @throws InvalidArgumentException If the number of operands isn't 1.
     */
    private static function validateCondition(string $operator, array $condition): ExpressionInterface|array|string|null
    {
        if (count($condition) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        $firstValue = array_shift($condition);

        if (
            is_array($firstValue)
            || $firstValue instanceof ExpressionInterface
            || is_string($firstValue)
            || $firstValue === null
        ) {
            return $firstValue;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires condition to be array, string, null or ExpressionInterface.",
        );
    }
}
