<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Schema\Column\BigIntColumn as BaseBigIntColumn;

final class BigIntColumn extends BaseBigIntColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
