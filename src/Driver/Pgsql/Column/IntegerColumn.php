<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Schema\Column\IntegerColumn as BaseIntegerColumn;

final class IntegerColumn extends BaseIntegerColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
