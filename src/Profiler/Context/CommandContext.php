<?php

declare(strict_types=1);

namespace JustQuery\Profiler\Context;

use Throwable;

final class CommandContext implements ContextInterface
{
    private ?Throwable $exception = null;

    /**
     * @param array<int|string, mixed>|null $params
     */
    public function __construct(
        public readonly string $method = '',
        public readonly string $category = '',
        public readonly ?string $sql = null,
        public readonly ?array $params = null,
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
