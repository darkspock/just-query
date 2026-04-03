<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql;

use JustQuery\Driver\Pdo\AbstractPdoCommand;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for PostgreSQL
 * Server.
 */
final class Command extends AbstractPdoCommand
{
    /**
     * @return array<int, string>
     */
    public function showDatabases(): array
    {
        $sql = <<<SQL
        SELECT datname FROM pg_database WHERE datistemplate = false AND datname NOT IN ('postgres', 'template0', 'template1')
        SQL;

        /** @var array<int, string> */
        return $this->setSql($sql)->queryColumn();
    }
}
