<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Data;

use JustQuery\Schema\Data\AbstractLazyArray;

final class LazyArray extends AbstractLazyArray
{
    protected function parse(string $value): ?array
    {
        return (new ArrayParser())->parse($value);
    }
}
