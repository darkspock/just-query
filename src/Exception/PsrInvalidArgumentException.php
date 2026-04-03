<?php

declare(strict_types=1);

namespace JustQuery\Exception;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Represents an exception that's caused by invalid operations of cache.
 */
final class PsrInvalidArgumentException extends Exception implements InvalidArgumentException {}
