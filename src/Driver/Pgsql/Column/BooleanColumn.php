<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Schema\Column\BooleanColumn as BaseBooleanColumn;

final class BooleanColumn extends BaseBooleanColumn
{
    public function phpTypecast(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== 'f';
    }
}
