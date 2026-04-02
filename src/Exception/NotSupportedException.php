<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Exception;

/**
 * Represents an exception caused by accessing features that aren't supported by the underlying DBMS.
 */
final class NotSupportedException extends Exception {}
