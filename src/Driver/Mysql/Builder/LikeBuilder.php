<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Mysql\Builder;

use FastPHP\QueryBuilder\QueryBuilder\Condition\Like;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotLike;

/**
 * Build an object of {@see Like} into SQL expressions for MySQL Server.
 */
final class LikeBuilder extends \FastPHP\QueryBuilder\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function prepareColumn(Like|NotLike $condition, array &$params): string
    {
        $column = parent::prepareColumn($condition, $params);

        if ($condition->caseSensitive === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
