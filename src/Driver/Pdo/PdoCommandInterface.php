<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pdo;

use PDOStatement;
use JustQuery\Command\CommandInterface;

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
