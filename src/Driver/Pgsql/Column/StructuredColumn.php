<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Driver\Pgsql\Data\StructuredLazyArray;
use JustQuery\Schema\Column\AbstractStructuredColumn;

use function is_string;

final class StructuredColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value
     * @return array<string, mixed>|null
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): ?array
    {
        if (is_string($value)) {
            return (new StructuredLazyArray($value, $this->columns))->getValue(); // @phpstan-ignore return.type
        }

        return $value;
    }
}
