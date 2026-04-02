<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Expression\Function;

use FastPHP\QueryBuilder\Expression\Function\Builder\GreatestBuilder;

/**
 * Represents a SQL `GREATEST()` function that returns the greatest value from a list of values or expressions.
 *
 * Example usage:
 *
 * ```php
 * $greatest = new Greatest(1, '(a + b)', $db->select('column')->from('table')->where(['id' => 1]));
 * ```
 *
 * ```sql
 * GREATEST(1, (a + b), (SELECT "column" FROM "table" WHERE "id" = 1))
 * ```
 *
 * @see GreatestBuilder for building SQL representations of this function expression.
 */
final class Greatest extends MultiOperandFunction {}
