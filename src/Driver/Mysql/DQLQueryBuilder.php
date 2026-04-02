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
use FastPHP\QueryBuilder\Query\QueryInterface;
use FastPHP\QueryBuilder\QueryBuilder\AbstractDQLQueryBuilder;
use FastPHP\QueryBuilder\QueryBuilder\Condition\JsonOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Like;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotLike;

use function array_map;
use function implode;
use function is_array;
use function is_string;
use function str_contains;

/**
 * Implements a DQL (Data Query Language) SQL statements for MySQL, MariaDB.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    /** @var array<string, list<array{type: string, indexes: list<string>}>> */
    private array $currentIndexHints = [];

    public function build(QueryInterface $query, array $params = []): array
    {
        $this->currentIndexHints = $query->getIndexHints();

        try {
            return parent::build($query, $params);
        } finally {
            $this->currentIndexHints = [];
        }
    }

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

    /**
     * @param array<int|string, mixed> $tables
     * @param array<int|string, mixed> $params
     *
     * @return array<int|string, mixed>
     */
    protected function quoteTableNames(array $tables, array &$params): array
    {
        if (empty($this->currentIndexHints)) {
            return parent::quoteTableNames($tables, $params);
        }

        foreach ($tables as $i => $table) {
            if ($table instanceof QueryInterface) {
                [$sql, $params] = $this->build($table, $params); // @phpstan-ignore argument.type
                $tables[$i] = "($sql) " . $this->quoter->quoteTableName((string) $i);
            } elseif (is_string($table) && is_string($i)) {
                // Aliased: ['alias' => 'tableName']
                $hint = $this->buildIndexHintSql($table);
                if (!str_contains($table, '(')) {
                    $table = $this->quoter->quoteTableName($table);
                }
                $tables[$i] = "$table " . $this->quoter->quoteTableName($i) . $hint;
            } elseif ($table instanceof ExpressionInterface) {
                $table = $this->buildExpression($table, $params);
                $tables[$i] = is_string($i)
                    ? "$table " . $this->quoter->quoteTableName($i)
                    : $table;
            } elseif (is_string($table) && !str_contains($table, '(')) {
                $tableWithAlias = $this->extractAlias($table);
                if (is_array($tableWithAlias)) {
                    $hint = $this->buildIndexHintSql($tableWithAlias[1]);
                    $tables[$i] = $this->quoter->quoteTableName($tableWithAlias[1]) . ' '
                        . $this->quoter->quoteTableName($tableWithAlias[2]) . $hint;
                } else {
                    $hint = $this->buildIndexHintSql($table);
                    $tables[$i] = $this->quoter->quoteTableName($table) . $hint;
                }
            }
        }

        return $tables;
    }

    protected function defaultExpressionBuilders(): array // @phpstan-ignore missingType.generics
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

    /**
     * Builds the SQL fragment for index hints on a given table.
     */
    private function buildIndexHintSql(string $tableName): string
    {
        if (!isset($this->currentIndexHints[$tableName])) {
            return '';
        }

        $parts = [];

        foreach ($this->currentIndexHints[$tableName] as $hint) {
            $quotedIndexes = array_map($this->quoter->quoteColumnName(...), $hint['indexes']);
            $parts[] = $hint['type'] . ' (' . implode(', ', $quotedIndexes) . ')';
        }

        return ' ' . implode(' ', $parts);
    }
}
