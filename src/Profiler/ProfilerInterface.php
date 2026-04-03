<?php

declare(strict_types=1);

namespace JustQuery\Profiler;

use JustQuery\Profiler\Context\ContextInterface;

interface ProfilerInterface
{
    public function begin(string $token, ContextInterface $context): void;

    public function end(string $token, ContextInterface $context): void;
}
