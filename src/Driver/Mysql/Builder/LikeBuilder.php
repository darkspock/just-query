<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql\Builder;

use JustQuery\QueryBuilder\Condition\Like;
use JustQuery\QueryBuilder\Condition\NotLike;

/**
 * Build an object of {@see Like} into SQL expressions for MySQL Server.
 */
final class LikeBuilder extends \JustQuery\QueryBuilder\Condition\Builder\LikeBuilder
{
    /**
     * @param array<int|string, mixed> $params
     */
    protected function prepareColumn(Like|NotLike $condition, array &$params): string
    {
        $column = parent::prepareColumn($condition, $params);

        if ($condition->caseSensitive === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
