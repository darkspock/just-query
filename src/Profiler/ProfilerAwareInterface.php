<?php

declare(strict_types=1);

namespace JustQuery\Profiler;

interface ProfilerAwareInterface
{
    public function setProfiler(?ProfilerInterface $profiler): void;
}
