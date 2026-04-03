<?php

declare(strict_types=1);

namespace JustQuery\Expression\Function\Builder;

use JustQuery\Expression\Function\Greatest;
use JustQuery\Expression\Function\Longest;
use JustQuery\Expression\Function\MultiOperandFunction;

/**
 * Builds SQL representation of function expressions which returns the longest string from a set of operands.
 *
 * ```SQL
 * (SELECT value FROM (
 *     SELECT "column1" AS value
 *     UNION
 *     SELECT "column2" AS value
 * ) AS t ORDER BY LENGTH(value) DESC LIMIT 1)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<Longest>
 */
final class LongestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the longest string.
     *
     * @param Greatest $expression The expression to build.
     * @param array<int|string, mixed> $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string // @phpstan-ignore method.childParameterType
    {
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $selects[] = 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value';
        }

        $unions = implode(' UNION ', $selects);

        return "(SELECT value FROM ($unions) AS t ORDER BY LENGTH(value) DESC LIMIT 1)";
    }
}
