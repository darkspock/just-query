<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

enum LikeConjunction
{
    case And;
    case Or;
}
