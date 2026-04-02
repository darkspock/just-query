<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

/**
 * Like condition modes.
 */
enum LikeMode
{
    case Contains;
    case StartsWith;
    case EndsWith;
    case Custom;
}
