<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Mysql;

use Psr\Log\LogLevel;
use Throwable;
use FastPHP\QueryBuilder\Connection\ServerInfoInterface;
use FastPHP\QueryBuilder\Driver\Pdo\AbstractPdoConnection;
use FastPHP\QueryBuilder\Driver\Pdo\PdoCommandInterface;
use FastPHP\QueryBuilder\Driver\Mysql\Column\ColumnBuilder;
use FastPHP\QueryBuilder\Driver\Mysql\Column\ColumnFactory;
use FastPHP\QueryBuilder\QueryBuilder\QueryBuilderInterface;
use FastPHP\QueryBuilder\Schema\Column\ColumnFactoryInterface;
use FastPHP\QueryBuilder\Schema\QuoterInterface;
use FastPHP\QueryBuilder\Schema\SchemaInterface;
use FastPHP\QueryBuilder\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for MySQL, MariaDB.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.php
 */
final class Connection extends AbstractPdoConnection
{
    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Closing DB connection: ' . $this->driver->getDsn() . ' ' . __METHOD__,
            );

            // Solution for close connections {@link https://stackoverflow.com/questions/18277233/pdo-closing-connection}
            try {
                $this->pdo->exec('KILL CONNECTION_ID()');
            } catch (Throwable) {
            }

            $this->pdo = null;
            $this->transaction = null;
        }
    }

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

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder ??= new QueryBuilder($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter ??= new Quoter('`', '`', $this->getTablePrefix());
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema ??= new Schema($this, $this->schemaCache);
    }

    public function getServerInfo(): ServerInfoInterface
    {
        return $this->serverInfo ??= new ServerInfo($this);
    }
}
