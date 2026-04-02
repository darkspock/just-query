<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Driver\Pgsql\Data\LazyArray;
use FastPHP\QueryBuilder\Schema\Column\AbstractArrayColumn;

use function is_string;

final class ArrayColumn extends AbstractArrayColumn
{
    /**
     * @param string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): ?array
    {
        if (is_string($value)) {
            return (new LazyArray($value, $this->getColumn(), $this->dimension))->getValue();
        }

        return $value;
    }
}
