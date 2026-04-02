<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pdo;

use PDOStatement;
use FastPHP\QueryBuilder\Command\CommandInterface;

/**
 * This interface defines the method {@see getPdoStatement()} that must be implemented by {@see \PDO}.
 *
 * @see CommandInterface
 */
interface PdoCommandInterface extends CommandInterface
{
    /**
     * @return PDOStatement|null The PDO statement.
     */
    public function getPdoStatement(): ?PDOStatement;
}
