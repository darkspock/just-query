<?php

declare(strict_types=1);

namespace JustQuery\Expression;

use Stringable;
use JustQuery\Connection\ConnectionInterface;
use JustQuery\Query\QueryInterface;

/**
 * Represents a DB expression that doesn't need escaping or quoting.
 *
 * When an Expression object is embedded within a SQL statement or fragment, it will be replaced with the
 * {@see expression} property value without any DB escaping or quoting.
 *
 * For example,
 *
 * ```php
 * $expression = new Expression('NOW()');
 * $now = $db->select($expression)->scalar();  // SELECT NOW();
 * echo $now; // prints the current date
 * ```
 *
 * Expression objects are mainly created for passing raw SQL expressions to methods of
 * {@see QueryInterface} and related classes.
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
final class Expression implements ExpressionInterface, Stringable
{
    /**
     * @param string $expression The DB expression.
     * @param array<int|string, mixed> $params List of parameters to bind to this expression. The keys are placeholders appearing in
     * {@see expression} and the values are the corresponding parameter values.
     *
     * @psalm-param ParamsType $params
     */
    public function __construct(
        public readonly string $expression,
        public readonly array $params = [],
    ) {}

    /**
     * @return string The expression.
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
