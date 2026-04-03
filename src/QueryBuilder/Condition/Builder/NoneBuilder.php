<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\None;

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
