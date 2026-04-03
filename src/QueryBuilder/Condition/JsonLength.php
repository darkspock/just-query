<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition;

use InvalidArgumentException;
use JustQuery\Expression\ExpressionInterface;

use function array_key_exists;
use function in_array;
use function is_string;

/**
 * Condition that represents `JSON LENGTH` comparison.
 *
 * Compares the length of a JSON array/object column against a value.
 *
 * ```php
 * // ['json length', 'tags', '>', 3]
 * // ['json length', 'options', '=', 0]
 * // ['json length', 'data', '>=', 1, '$.items']
 * ```
 */
final class JsonLength implements ConditionInterface
{
    private const VALID_OPERATORS = ['=', '!=', '<>', '>', '>=', '<', '<='];

    /**
     * @param ExpressionInterface|string $column The JSON column name.
     * @param string $operator The comparison operator.
     * @param int $length The length to compare against.
     * @param string|null $path Optional JSON path (e.g. '$.items').
     */
    public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly string $operator,
        public readonly int $length,
        public readonly ?string $path = null,
    ) {
        if (!in_array($operator, self::VALID_OPERATORS, true)) {
            throw new InvalidArgumentException("Invalid operator \"$operator\" for JSON LENGTH condition.");
        }
    }

    public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (!array_key_exists(0, $operands)) {
            throw new InvalidArgumentException("Operator \"$operator\" requires column as first operand.");
        }
        if (!array_key_exists(1, $operands)) {
            throw new InvalidArgumentException("Operator \"$operator\" requires comparison operator as second operand.");
        }
        if (!array_key_exists(2, $operands)) {
            throw new InvalidArgumentException("Operator \"$operator\" requires length value as third operand.");
        }

        $column = $operands[0];
        if (!is_string($column) && !$column instanceof ExpressionInterface) {
            throw new InvalidArgumentException("Operator \"$operator\" requires column to be string or ExpressionInterface.");
        }

        /** @var string $op */
        $op = $operands[1];
        /** @var int $length */
        $length = $operands[2];
        /** @var string|null $path */
        $path = $operands[3] ?? null;
        return new self($column, (string) $op, (int) $length, $path);
    }
}
