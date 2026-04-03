<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql;

use Psr\Log\LogLevel;
use PDO;
use SensitiveParameter;
use Stringable;
use Throwable;
use JustQuery\Cache\SchemaCache;
use JustQuery\Connection\ServerInfoInterface;
use JustQuery\Driver\Pdo\AbstractPdoConnection;
use JustQuery\Driver\Pdo\PdoCommandInterface;
use JustQuery\Driver\Mysql\Column\ColumnBuilder;
use JustQuery\Driver\Mysql\Column\ColumnFactory;
use JustQuery\QueryBuilder\QueryBuilderInterface;
use JustQuery\Schema\Column\ColumnFactoryInterface;
use JustQuery\Schema\QuoterInterface;
use JustQuery\Schema\SchemaInterface;
use JustQuery\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for MySQL, MariaDB.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.php
 */
final class Connection extends AbstractPdoConnection
{
    /**
     * @param array<int, mixed> $attributes
     */
    public static function fromDsn(
        string|Stringable $dsn,
        string $username = '',
        #[SensitiveParameter] string $password = '',
        array $attributes = [],
        ?SchemaCache $schemaCache = null,
        ?ColumnFactoryInterface $columnFactory = null,
    ): self {
        return new self(
            new Driver($dsn, $username, $password, $attributes),
            $schemaCache ?? self::createDefaultSchemaCache(),
            $columnFactory,
        );
    }

    public static function fromPdo(
        PDO $pdo,
        ?SchemaCache $schemaCache = null,
        ?ColumnFactoryInterface $columnFactory = null,
    ): self {
        $connection = new self(
            new Driver(''),
            $schemaCache ?? self::createDefaultSchemaCache(),
            $columnFactory,
        );
        $connection->setSharedPdo($pdo);

        return $connection;
    }

    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Closing DB connection: ' . $this->driver->getDsn() . ' ' . __METHOD__,
            );

            // Solution for close connections {@link https://stackoverflow.com/questions/18277233/pdo-closing-connection}
            if (!$this->isUsingSharedPdo()) {
                try {
                    $this->pdo->exec('KILL CONNECTION_ID()');
                } catch (Throwable) {
                }
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

        /** @phpstan-ignore argument.type */
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
