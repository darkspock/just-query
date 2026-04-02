<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Unit;

use FastPHP\QueryBuilder\Profiler\Context\CommandContext;
use FastPHP\QueryBuilder\Profiler\QueryProfiler;
use PHPUnit\Framework\TestCase;

final class QueryProfilerTest extends TestCase
{
    public function testEmptyProfiler(): void
    {
        $profiler = new QueryProfiler();

        self::assertSame(0, $profiler->getCount());
        self::assertSame(0.0, $profiler->getTotalTime());
        self::assertSame([], $profiler->getQueries());
    }

    public function testCollectsQueries(): void
    {
        $profiler = new QueryProfiler();
        $ctx = new CommandContext('test', 'query', 'SELECT 1', []);

        $profiler->begin('SELECT 1', $ctx);
        $profiler->end('SELECT 1', $ctx);

        self::assertSame(1, $profiler->getCount());
        self::assertCount(1, $profiler->getQueries());
        self::assertSame('SELECT 1', $profiler->getQueries()[0]['sql']);
    }

    public function testTracksParams(): void
    {
        $profiler = new QueryProfiler();
        $params = [':id' => 1, ':name' => 'test'];
        $ctx = new CommandContext('test', 'query', 'SELECT * FROM users WHERE id = :id', $params);

        $profiler->begin('SELECT * FROM users WHERE id = :id', $ctx);
        $profiler->end('SELECT * FROM users WHERE id = :id', $ctx);

        self::assertSame($params, $profiler->getQueries()[0]['params']);
    }

    public function testTracksErrors(): void
    {
        $profiler = new QueryProfiler();
        $ctx = new CommandContext('test', 'query', 'SELECT * FROM nonexistent');
        $ctx->setException(new \RuntimeException('Table not found'));

        $profiler->begin('SELECT * FROM nonexistent', $ctx);
        $profiler->end('SELECT * FROM nonexistent', $ctx);

        self::assertCount(1, $profiler->getErrors());
        self::assertSame('Table not found', $profiler->getErrors()[0]['error']);
    }

    public function testNoErrorsWhenClean(): void
    {
        $profiler = new QueryProfiler();
        $ctx = new CommandContext('test', 'query', 'SELECT 1');

        $profiler->begin('SELECT 1', $ctx);
        $profiler->end('SELECT 1', $ctx);

        self::assertCount(0, $profiler->getErrors());
        self::assertNull($profiler->getQueries()[0]['error']);
    }

    public function testTimingIsPositive(): void
    {
        $profiler = new QueryProfiler();
        $ctx = new CommandContext('test', 'query', 'SELECT 1');

        $profiler->begin('SELECT 1', $ctx);
        usleep(1000); // 1ms
        $profiler->end('SELECT 1', $ctx);

        self::assertGreaterThan(0, $profiler->getQueries()[0]['time']);
        self::assertGreaterThan(0, $profiler->getTotalTime());
    }

    public function testGetSlowest(): void
    {
        $profiler = new QueryProfiler();

        for ($i = 1; $i <= 5; $i++) {
            $sql = "SELECT $i";
            $ctx = new CommandContext('test', 'query', $sql);
            $profiler->begin($sql, $ctx);
            usleep($i * 500); // increasing delay
            $profiler->end($sql, $ctx);
        }

        $slowest = $profiler->getSlowest(2);
        self::assertCount(2, $slowest);
        self::assertGreaterThanOrEqual($slowest[1]['time'], $slowest[0]['time']);
    }

    public function testReset(): void
    {
        $profiler = new QueryProfiler();
        $ctx = new CommandContext('test', 'query', 'SELECT 1');

        $profiler->begin('SELECT 1', $ctx);
        $profiler->end('SELECT 1', $ctx);
        self::assertSame(1, $profiler->getCount());

        $profiler->reset();
        self::assertSame(0, $profiler->getCount());
        self::assertSame([], $profiler->getQueries());
    }

    public function testMultipleQueries(): void
    {
        $profiler = new QueryProfiler();

        for ($i = 0; $i < 10; $i++) {
            $sql = "SELECT $i";
            $ctx = new CommandContext('test', 'query', $sql);
            $profiler->begin($sql, $ctx);
            $profiler->end($sql, $ctx);
        }

        self::assertSame(10, $profiler->getCount());
        self::assertCount(10, $profiler->getQueries());
    }
}
