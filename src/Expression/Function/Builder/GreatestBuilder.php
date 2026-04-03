<?php

declare(strict_types=1);

namespace JustQuery\Expression\Function\Builder;

use JustQuery\Expression\Function\Greatest;
use JustQuery\Expression\Function\MultiOperandFunction;

use function implode;

/**
 * Builds SQL `GREATEST()` function expressions for {@see Greatest} objects.
 *
 * @extends MultiOperandFunctionBuilder<Greatest>
 */
final class GreatestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL `GREATEST()` function expression from the given {@see Greatest} object.
     *
     * @param Greatest $expression The expression to build.
     * @param array<int|string, mixed> $params The parameters to bind.
     *
     * @return string The SQL `GREATEST()` function expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $builtOperands = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperands[] = $this->buildOperand($operand, $params);
        }

        return 'GREATEST(' . implode(', ', $builtOperands) . ')';
    }
}
