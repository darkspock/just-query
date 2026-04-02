<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Mysql;

use FastPHP\QueryBuilder\Profiler\QueryProfiler;
use FastPHP\QueryBuilder\Query\Query;

final class ProfilerIntegrationTest extends MysqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
        $this->seedUsers();
    }

    protected function tearDown(): void
    {
        $this->getDb()->setProfiler(null);
        $this->cleanUp();
    }

    public function testProfilerCollectsRealQueries(): void
    {
        $profiler = new QueryProfiler();
        $this->getDb()->setProfiler($profiler);

        (new Query($this->getDb()))->from('test_users')->all();
        (new Query($this->getDb()))->from('test_users')->where(['name' => 'Alice'])->one();
        (new Query($this->getDb()))->from('test_users')->count('*');

        self::assertSame(3, $profiler->getCount());
        self::assertGreaterThan(0, $profiler->getTotalTime());

        foreach ($profiler->getQueries() as $q) {
            self::assertNotEmpty($q['sql']);
            self::assertGreaterThanOrEqual(0, $q['time']);
            self::assertNull($q['error']);
        }
    }

    public function testProfilerCapturesErrors(): void
    {
        $profiler = new QueryProfiler();
        $this->getDb()->setProfiler($profiler);

        try {
            $this->getDb()->createCommand('SELECT * FROM nonexistent_table_xyz')->queryAll();
        } catch (\Throwable) {
            // expected
        }

        self::assertSame(1, $profiler->getCount());
        self::assertCount(1, $profiler->getErrors());
        self::assertStringContainsString('nonexistent_table_xyz', $profiler->getErrors()[0]['error']);
    }

    public function testProfilerZeroOverheadWhenDisabled(): void
    {
        $this->getDb()->setProfiler(null);

        // Should work fine without profiler
        $rows = (new Query($this->getDb()))->from('test_users')->all();
        self::assertCount(5, $rows);
    }

    public function testProfilerGetSlowestWithRealQueries(): void
    {
        $profiler = new QueryProfiler();
        $this->getDb()->setProfiler($profiler);

        for ($i = 0; $i < 5; $i++) {
            (new Query($this->getDb()))->from('test_users')->all();
        }

        $slowest = $profiler->getSlowest(3);
        self::assertCount(3, $slowest);
        self::assertGreaterThanOrEqual($slowest[1]['time'], $slowest[0]['time']);
    }
}
