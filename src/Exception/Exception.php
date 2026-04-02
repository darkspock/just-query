<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Exception;

use Stringable;

use const PHP_EOL;

/**
 * Represents an exception that's caused by some DB-related operations.
 *
 * It provides more information about the error that's caused by the exception.
 */
class Exception extends \Exception implements Stringable
{
    /**
     * @param array<int|string, mixed>|null $errorInfo
     */
    public function __construct(string $message, public ?array $errorInfo = [], ?\Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string Readable representation of exception.
     */
    public function __toString(): string
    {
        return parent::__toString() . PHP_EOL . 'Additional Information:' . PHP_EOL . print_r($this->errorInfo, true);
    }
}
