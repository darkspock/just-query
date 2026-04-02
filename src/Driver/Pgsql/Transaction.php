<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql;

use FastPHP\QueryBuilder\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the PostgreSQL Server specific transaction.
 */
final class Transaction extends AbstractPdoTransaction {}
