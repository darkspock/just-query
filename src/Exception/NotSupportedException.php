<?php

declare(strict_types=1);

namespace JustQuery\Exception;

/**
 * Represents an exception caused by accessing features that aren't supported by the underlying DBMS.
 */
final class NotSupportedException extends Exception {}
