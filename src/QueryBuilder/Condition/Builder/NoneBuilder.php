<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition\Builder;

use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\QueryBuilder\Condition\None;

/**
 * Builds SQL expressions for {@see None} condition.
 *
 * @implements ExpressionBuilderInterface<None>
 */
final class NoneBuilder implements ExpressionBuilderInterface
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return '0=1';
    }
}
