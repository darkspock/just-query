<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\QueryBuilder\Condition\Like;
use JustQuery\QueryBuilder\Condition\NotLike;

/**
 * Build an object of {@see Like} into SQL expressions for PostgreSQL Server.
 */
final class LikeBuilder extends \JustQuery\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function getOperatorData(Like|NotLike $condition): array
    {
        return match ($condition::class) {
            Like::class => [false, $condition->caseSensitive === false ? 'ILIKE' : 'LIKE'],
            NotLike::class => [true, $condition->caseSensitive === false ? 'NOT ILIKE' : 'NOT LIKE'],
        };
    }
}
