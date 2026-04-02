<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Expression\Value\Builder;

use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Value\Param;

use function count;

/**
 * Implements the {@see ExpressionBuilderInterface} interface, is used to build {@see Param} objects.
 *
 * @implements ExpressionBuilderInterface<ExpressionInterface>
 */
final class ParamBuilder implements ExpressionBuilderInterface
{
    public const PARAM_PREFIX = ':pv';

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $placeholder = self::PARAM_PREFIX . count($params);
        $additionalCount = 0;

        while (isset($params[$placeholder])) {
            $placeholder = self::PARAM_PREFIX . count($params) . '_' . $additionalCount;
            ++$additionalCount;
        }

        $params[$placeholder] = $expression;

        return $placeholder;
    }
}
