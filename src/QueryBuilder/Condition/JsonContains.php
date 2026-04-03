<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition;

use InvalidArgumentException;
use JustQuery\Expression\ExpressionInterface;

use function array_key_exists;
use function is_string;

/**
 * Condition that represents `JSON CONTAINS` operator.
 *
 * Checks if a JSON column contains a given value, optionally at a specific path.
 *
 * ```php
 * // ['json contains', 'options', 'en']
 * // ['json contains', 'options', ['role' => 'admin']]
 * // ['json contains', 'options', 'en', '$.languages']
 * ```
 */
final class JsonContains implements ConditionInterface
{
    /**
     * @param ExpressionInterface|string $column The JSON column name.
     * @param mixed $value The value to check for containment.
     * @param string|null $path Optional JSON path (e.g. '$.languages').
     */
    public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly mixed $value,
        public readonly ?string $path = null,
    ) {}

    public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (!array_key_exists(0, $operands)) {
            throw new InvalidArgumentException("Operator \"$operator\" requires column as first operand.");
        }
        if (!array_key_exists(1, $operands)) {
            throw new InvalidArgumentException("Operator \"$operator\" requires value as second operand.");
        }

        $column = $operands[0];
        if (!is_string($column) && !$column instanceof ExpressionInterface) {
            throw new InvalidArgumentException("Operator \"$operator\" requires column to be string or ExpressionInterface.");
        }

        /** @var string|null $path */
        $path = $operands[2] ?? null;
        return new self($column, $operands[1], $path);
    }
}
