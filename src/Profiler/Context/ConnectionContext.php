<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler\Context;

final class ConnectionContext implements ContextInterface
{
    public function __construct(
        public readonly string $method = '',
    ) {}
}
