<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Data\LazyArray;
use JustQuery\Schema\Column\AbstractArrayColumn;

use function is_string;

final class ArrayColumn extends AbstractArrayColumn
{
    /**
     * @param string|null $value
     * @return array<int|string, mixed>|null
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
