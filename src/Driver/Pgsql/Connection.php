<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql;

use FastPHP\QueryBuilder\Driver\Pdo\AbstractPdoConnection;
use FastPHP\QueryBuilder\Driver\Pdo\PdoCommandInterface;
use InvalidArgumentException;
use FastPHP\QueryBuilder\Driver\Pgsql\Column\ColumnBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Column\ColumnFactory;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;
use FastPHP\QueryBuilder\Schema\Column\ColumnFactoryInterface;
use FastPHP\QueryBuilder\Schema\QuoterInterface;
use FastPHP\QueryBuilder\Schema\SchemaInterface;
use FastPHP\QueryBuilder\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for PostgreSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-pgsql.php
 */
final class Connection extends AbstractPdoConnection
{
    public function createCommand(?string $sql = null, array $params = []): PdoCommandInterface
    {
        $command = new Command($this);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new Transaction($this);
    }

    public function getColumnBuilderClass(): string
    {
        return ColumnBuilder::class;
    }

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return $this->columnFactory ??= new ColumnFactory();
    }

    public function getLastInsertId(?string $sequenceName = null): string
    {
        if ($sequenceName === null) {
            throw new InvalidArgumentException('PostgreSQL not support lastInsertId without sequence name.');
        }

        return parent::getLastInsertId($sequenceName);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder ??= new QueryBuilder($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter ??= new Quoter('"', '"', $this->getTablePrefix());
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema ??= new Schema($this, $this->schemaCache);
    }
}
