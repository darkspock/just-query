<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition;

enum LikeConjunction
{
    case And;
    case Or;
}
