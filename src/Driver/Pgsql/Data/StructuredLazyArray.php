<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Data;

use FastPHP\QueryBuilder\Schema\Data\AbstractStructuredLazyArray;

final class StructuredLazyArray extends AbstractStructuredLazyArray
{
    /** @return array<string, mixed>|null */
    protected function parse(string $value): ?array // @phpstan-ignore return.unusedType
    {
        return (new StructuredParser())->parse($value); // @phpstan-ignore return.type
    }
}
