<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Mysql;

use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Function\ArrayMerge;
use FastPHP\QueryBuilder\Expression\Function\Longest;
use FastPHP\QueryBuilder\Expression\Function\Shortest;
use FastPHP\QueryBuilder\Driver\Mysql\Builder\ArrayMergeBuilder;
use FastPHP\QueryBuilder\Driver\Mysql\Builder\JsonOverlapsBuilder;
use FastPHP\QueryBuilder\Driver\Mysql\Builder\LikeBuilder;
use FastPHP\QueryBuilder\Driver\Mysql\Builder\LongestBuilder;
use FastPHP\QueryBuilder\Driver\Mysql\Builder\ShortestBuilder;
use FastPHP\QueryBuilder\QueryBuilder\AbstractDQLQueryBuilder;
use FastPHP\QueryBuilder\QueryBuilder\Condition\JsonOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Like;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotLike;

/**
 * Implements a DQL (Data Query Language) SQL statements for MySQL, MariaDB.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
    {
        if (!empty($offset)) {
            /**
             * Limit isn't optional in MySQL.
             *
             * @link https://stackoverflow.com/a/271650/1106908
             * @link https://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $limit = $limit instanceof ExpressionInterface
                ? $this->buildExpression($limit)
                : $limit ?? '18446744073709551615'; // 2^64-1

            $offset = $offset instanceof ExpressionInterface
                ? $this->buildExpression($offset)
                : (string) $offset;

            return "LIMIT $limit OFFSET $offset";
        }

        if ($limit !== null) {
            $limit = $limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string) $limit;

            return "LIMIT $limit";
        }

        return '';
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
            Longest::class => LongestBuilder::class,
            Shortest::class => ShortestBuilder::class,
        ];
    }
}
