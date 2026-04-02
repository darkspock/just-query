<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

use InvalidArgumentException;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilder;

/**
 * Should be implemented by classes that represent a condition in the {@see QueryBuilder}.
 */
interface ConditionInterface extends ExpressionInterface
{
    /**
     * Creates object by array-definition.
     *
     * @param string $operator Operator in uppercase.
     * @param array $operands Array of corresponding operands
     *
     * @throws InvalidArgumentException If input parameters aren't suitable for this condition.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self;
}
