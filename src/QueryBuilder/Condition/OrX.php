<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

use FastPHP\QueryBuilder\Expression\ExpressionInterface;

/**
 * Condition that connects two or more SQL expressions with the `OR` operator.
 */
final class OrX implements ConditionInterface
{
    /**
     * @var array<int, array<int|string, mixed>|bool|ExpressionInterface|float|int|string>
     * @psalm-var array<array|ExpressionInterface|scalar>
     */
    public readonly array $expressions; // @phpstan-ignore missingType.iterableValue

    /**
     * @param array<int|string, mixed>|bool|ExpressionInterface|float|int|string ...$expressions The expressions that are connected by this condition.
     */
    public function __construct(
        array|ExpressionInterface|int|float|bool|string ...$expressions,
    ) {
        $this->expressions = $expressions;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self(...$operands); // @phpstan-ignore argument.type
    }
}
