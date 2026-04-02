<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler\Context;

final class CommandContext implements ContextInterface
{
    public function __construct(
        public readonly string $method = '',
        public readonly ?string $sql = null,
        public readonly ?array $params = null,
    ) {}
}
