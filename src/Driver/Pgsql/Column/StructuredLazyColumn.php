<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Data\StructuredLazyArray;
use JustQuery\Schema\Column\AbstractStructuredColumn;

use function is_string;

final class StructuredLazyColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): ?StructuredLazyArray
    {
        if (is_string($value)) {
            return new StructuredLazyArray($value, $this->columns);
        }

        return $value;
    }
}
