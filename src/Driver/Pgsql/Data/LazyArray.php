<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Data;

use FastPHP\QueryBuilder\Schema\Data\AbstractLazyArray;

final class LazyArray extends AbstractLazyArray
{
    protected function parse(string $value): ?array
    {
        return (new ArrayParser())->parse($value);
    }
}
