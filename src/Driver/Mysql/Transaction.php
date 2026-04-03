<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql;

use JustQuery\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the MySQL, MariaDB specific transaction.
 */
final class Transaction extends AbstractPdoTransaction {}
