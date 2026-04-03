<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Expression\Builder;

use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Driver\Pgsql\Expression\DateRangeValue;
use JustQuery\Driver\Pgsql\Expression\Int4RangeValue;
use JustQuery\Driver\Pgsql\Expression\Int8RangeValue;
use JustQuery\Driver\Pgsql\Expression\NumRangeValue;
use JustQuery\Driver\Pgsql\Expression\TsRangeValue;
use JustQuery\Driver\Pgsql\Expression\TsTzRangeValue;
use JustQuery\Schema\Column\ColumnInterface;

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
