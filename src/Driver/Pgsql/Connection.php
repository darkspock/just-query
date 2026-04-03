<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql;

use PDO;
use SensitiveParameter;
use Stringable;
use JustQuery\Cache\SchemaCache;
use JustQuery\Driver\Pdo\AbstractPdoConnection;
use JustQuery\Driver\Pdo\PdoCommandInterface;
use InvalidArgumentException;
use JustQuery\Driver\Pgsql\Column\ColumnBuilder;
use JustQuery\Driver\Pgsql\Column\ColumnFactory;
use JustQuery\QueryBuilder\QueryBuilderInterface;
use JustQuery\Schema\Column\ColumnFactoryInterface;
use JustQuery\Schema\QuoterInterface;
use JustQuery\Schema\SchemaInterface;
use JustQuery\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for PostgreSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-pgsql.php
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
