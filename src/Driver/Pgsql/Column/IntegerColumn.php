<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Column;

use FastPHP\QueryBuilder\Schema\Column\IntegerColumn as BaseIntegerColumn;

final class IntegerColumn extends BaseIntegerColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
