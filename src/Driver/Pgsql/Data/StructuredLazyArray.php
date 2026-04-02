<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Data;

use FastPHP\QueryBuilder\Schema\Data\AbstractStructuredLazyArray;

final class StructuredLazyArray extends AbstractStructuredLazyArray
{
    protected function parse(string $value): ?array
    {
        return (new StructuredParser())->parse($value);
    }
}
