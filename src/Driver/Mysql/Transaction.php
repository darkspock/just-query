<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Mysql;

use FastPHP\QueryBuilder\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the MySQL, MariaDB specific transaction.
 */
final class Transaction extends AbstractPdoTransaction {}
