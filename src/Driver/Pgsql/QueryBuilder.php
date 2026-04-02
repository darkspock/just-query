<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql;

use FastPHP\QueryBuilder\Connection\ConnectionInterface;
use FastPHP\QueryBuilder\Driver\Pgsql\Column\ColumnDefinitionBuilder;
use FastPHP\QueryBuilder\QueryBuilder\AbstractQueryBuilder;

use function bin2hex;

/**
 * Implements the PostgreSQL Server specific query builder.
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

    protected function prepareBinary(string $binary): string
    {
        return "'\x" . bin2hex($binary) . "'::bytea";
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
