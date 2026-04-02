<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder;

use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Expression\ExpressionBuilderInterface;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\DateRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int4RangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int8RangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\NumRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsTzRangeValue;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;

/**
 * @template T as DateRangeValue|Int4RangeValue|Int8RangeValue|NumRangeValue|TsRangeValue|TsTzRangeValue
 * @implements ExpressionBuilderInterface<T>
 */
abstract class AbstractRangeValueBuilder implements ExpressionBuilderInterface
{
    final public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $this->getBoundColumn();
        return '\''
            . ($expression->includeLower ? '[' : '(')
            . $this->prepareBoundValue($expression->lower, $column)
            . ','
            . $this->prepareBoundValue($expression->upper, $column)
            . ($expression->includeUpper ? ']' : ')')
            . '\'';
    }

    abstract protected function getBoundColumn(): ColumnInterface;

    /**
     * @throws NotSupportedException
     */
    private function prepareBoundValue(mixed $value, ColumnInterface $boundColumn): string
    {
        $value = $boundColumn->dbTypecast($value);
        if ($value === null) {
            return '';
        }

        return (string) $value; // @phpstan-ignore cast.string
    }
}
