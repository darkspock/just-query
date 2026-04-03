<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql;

use JustQuery\Connection\ConnectionInterface;
use JustQuery\Driver\Mysql\Column\ColumnDefinitionBuilder;
use JustQuery\QueryBuilder\AbstractQueryBuilder;

/**
 * Implements MySQL, MariaDB specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    public function __construct(ConnectionInterface $db)
    {
        $quoter = $db->getQuoter();
        $schema = $db->getSchema();

        parent::__construct(
            $db,
            new DDLQueryBuilder($this, $quoter, $schema),
            new DMLQueryBuilder($this, $quoter, $schema),
            new DQLQueryBuilder($this, $quoter),
            new ColumnDefinitionBuilder($this),
        );
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
