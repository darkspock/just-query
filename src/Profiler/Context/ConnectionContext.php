<?php

declare(strict_types=1);

namespace JustQuery\Profiler\Context;

use Throwable;

final class ConnectionContext implements ContextInterface
{
    private ?Throwable $exception = null;

    public function __construct(
        public readonly string $method = '',
    ) {}

    public function setException(Throwable $e): self
    {
        $this->exception = $e;
        return $this;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
