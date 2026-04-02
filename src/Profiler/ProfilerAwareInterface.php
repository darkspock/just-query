<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler;

interface ProfilerAwareInterface
{
    public function setProfiler(?ProfilerInterface $profiler): void;
}
