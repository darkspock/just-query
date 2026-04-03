<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql;

use JustQuery\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the PostgreSQL Server specific transaction.
 */
final class Transaction extends AbstractPdoTransaction {}
