<?php

declare(strict_types=1);

namespace JustQuery\Expression\Value\Builder;

use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\Param;

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
