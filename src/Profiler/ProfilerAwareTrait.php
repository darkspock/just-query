<?php

declare(strict_types=1);

namespace JustQuery\Profiler;

trait ProfilerAwareTrait
{
    protected ?ProfilerInterface $profiler = null;

    public function setProfiler(?ProfilerInterface $profiler): void
    {
        $this->profiler = $profiler;
    }
}
