<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Schema\Column\BinaryColumn as BaseBinaryColumn;
use FastPHP\QueryBuilder\Schema\Data\StringableStream;

use function hex2bin;
use function is_string;
use function str_starts_with;
use function substr;

final class BinaryColumn extends BaseBinaryColumn
{
    public function phpTypecast(mixed $value): StringableStream|string|null
    {
        if (is_string($value) && str_starts_with($value, '\x')) {
            /** @var string */
            return hex2bin(substr($value, 2));
        }

        return parent::phpTypecast($value);
    }
}
