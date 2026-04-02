<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler;

use FastPHP\QueryBuilder\Profiler\Context\ContextInterface;

interface ProfilerInterface
{
    public function begin(string $token, ContextInterface $context): void;

    public function end(string $token, ContextInterface $context): void;
}
