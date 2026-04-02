<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Schema\Column\BigIntColumn as BaseBigIntColumn;

final class BigIntColumn extends BaseBigIntColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
