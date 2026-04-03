<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Schema\Column\BooleanColumn as BaseBooleanColumn;

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
