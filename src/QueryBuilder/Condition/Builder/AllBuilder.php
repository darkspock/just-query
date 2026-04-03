<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\All;

/**
 * Builds SQL expressions for {@see All} condition.
 *
 * @implements ExpressionBuilderInterface<All>
 */
final class AllBuilder implements ExpressionBuilderInterface
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return '';
    }
}
