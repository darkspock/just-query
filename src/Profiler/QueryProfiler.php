<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Profiler;

use FastPHP\QueryBuilder\Profiler\Context\CommandContext;
use FastPHP\QueryBuilder\Profiler\Context\ContextInterface;

/**
 * Simple query profiler that collects executed queries with timing.
 *
 * Usage:
 *   $profiler = new QueryProfiler();
 *   $connection->setProfiler($profiler);
 *
 *   // ... execute queries ...
 *
 *   $profiler->getQueries();    // all queries with SQL, time, params
 *   $profiler->getTotalTime();  // total execution time
 *   $profiler->getCount();      // number of queries
 *   $profiler->reset();         // clear collected data
 */
final class QueryProfiler implements ProfilerInterface
{
    /** @var array{sql: string, time: float, params: ?array, error: ?string}[] */
    private array $queries = [];

    /** @var array<string, float> */
    private array $timers = [];

    public function begin(string $token, ContextInterface $context): void
    {
        $this->timers[$token] = hrtime(true);
    }

    public function end(string $token, ContextInterface $context): void
    {
        $startTime = $this->timers[$token] ?? hrtime(true);
        $elapsed = (hrtime(true) - $startTime) / 1_000_000; // nanoseconds → milliseconds

        $entry = [
            'sql' => $token,
            'time' => round($elapsed, 3),
            'params' => null,
            'error' => null,
        ];

        if ($context instanceof CommandContext) {
            $entry['params'] = $context->params;
            $exception = $context->getException();
            if ($exception !== null) {
                $entry['error'] = $exception->getMessage();
            }
        }

        $this->queries[] = $entry;
        unset($this->timers[$token]);
    }

    /** @return array{sql: string, time: float, params: ?array, error: ?string}[] */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /** Total time in milliseconds. */
    public function getTotalTime(): float
    {
        return round(array_sum(array_column($this->queries, 'time')), 3);
    }

    public function getCount(): int
    {
        return count($this->queries);
    }

    /** @return array{sql: string, time: float, params: ?array, error: ?string}[] */
    public function getSlowest(int $limit = 10): array
    {
        $queries = $this->queries;
        usort($queries, static fn (array $a, array $b) => $b['time'] <=> $a['time']);
        return array_slice($queries, 0, $limit);
    }

    /** @return array{sql: string, time: float, params: ?array, error: ?string}[] */
    public function getErrors(): array
    {
        return array_values(array_filter($this->queries, static fn (array $q) => $q['error'] !== null));
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->timers = [];
    }
}
