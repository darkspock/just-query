<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler;

trait ProfilerAwareTrait
{
    protected ?ProfilerInterface $profiler = null;

    public function setProfiler(?ProfilerInterface $profiler): void
    {
        $this->profiler = $profiler;
    }
}
