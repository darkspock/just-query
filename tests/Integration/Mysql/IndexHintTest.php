<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Mysql;

use FastPHP\QueryBuilder\Query\Query;

final class IndexHintTest extends MysqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
        $this->createOrdersTable();
        $this->seedUsers();
        $this->seedOrders();

        $db = $this->getDb();
        $db->createCommand('CREATE INDEX idx_email ON test_users (email)')->execute();
        $db->createCommand('CREATE INDEX idx_age ON test_users (age)')->execute();
        $db->createCommand('CREATE INDEX idx_user_id ON test_orders (user_id)')->execute();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testForceIndexSqlGeneration(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->select('*')
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->where(['email' => 'alice@example.com']);

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('FORCE INDEX', $sql);
        self::assertStringContainsString('idx_email', $sql);
    }

    public function testForceIndexExecutesSuccessfully(): void
    {
        $db = $this->getDb();
        $rows = (new Query($db))
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->where(['email' => 'alice@example.com'])
            ->all();

        self::assertCount(1, $rows);
        self::assertSame('Alice', $rows[0]['name']);
    }

    public function testUseIndexSqlGeneration(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->useIndex('test_users', ['idx_email', 'idx_age']);

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('USE INDEX', $sql);
        self::assertStringContainsString('idx_email', $sql);
        self::assertStringContainsString('idx_age', $sql);
    }

    public function testUseIndexExecutesSuccessfully(): void
    {
        $db = $this->getDb();
        $rows = (new Query($db))
            ->from('test_users')
            ->useIndex('test_users', ['idx_email', 'idx_age'])
            ->where(['>', 'age', 25])
            ->all();

        self::assertCount(3, $rows);
    }

    public function testIgnoreIndexSqlGeneration(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->ignoreIndex('test_users', 'idx_email');

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('IGNORE INDEX', $sql);
        self::assertStringContainsString('idx_email', $sql);
    }

    public function testIgnoreIndexExecutesSuccessfully(): void
    {
        $db = $this->getDb();
        $rows = (new Query($db))
            ->from('test_users')
            ->ignoreIndex('test_users', 'idx_email')
            ->all();

        self::assertCount(5, $rows);
    }

    public function testForceIndexWithAlias(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->select('*')
            ->from(['u' => 'test_users'])
            ->forceIndex('test_users', 'idx_email')
            ->where(['u.email' => 'bob@example.com']);

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('FORCE INDEX', $sql);

        $rows = $query->all();
        self::assertCount(1, $rows);
        self::assertSame('Bob', $rows[0]['name']);
    }

    public function testForceIndexOnJoinTable(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->select(['test_users.name', 'test_orders.product'])
            ->from('test_users')
            ->innerJoin('test_orders', 'test_users.id = test_orders.user_id')
            ->forceIndex('test_orders', 'idx_user_id')
            ->where(['test_orders.status' => 'completed']);

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('FORCE INDEX', $sql);
        self::assertStringContainsString('idx_user_id', $sql);

        $rows = $query->all();
        self::assertCount(3, $rows);
    }

    public function testMultipleIndexHintsOnSameTable(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->useIndex('test_users', 'idx_age');

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('FORCE INDEX', $sql);
        self::assertStringContainsString('USE INDEX', $sql);
    }

    public function testIndexHintsOnMultipleTables(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->select(['test_users.name', 'test_orders.product'])
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->innerJoin('test_orders', 'test_users.id = test_orders.user_id')
            ->forceIndex('test_orders', 'idx_user_id');

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringContainsString('FORCE INDEX', $sql);
        self::assertStringContainsString('idx_email', $sql);
        self::assertStringContainsString('idx_user_id', $sql);

        $rows = $query->all();
        self::assertGreaterThan(0, count($rows));
    }

    public function testNoHintsProducesNormalSql(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->where(['name' => 'Alice']);

        $command = $query->createCommand();
        $sql = $command->getSql();

        self::assertStringNotContainsString('FORCE INDEX', $sql);
        self::assertStringNotContainsString('USE INDEX', $sql);
        self::assertStringNotContainsString('IGNORE INDEX', $sql);
    }

    public function testGetIndexHints(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->useIndex('test_orders', ['idx_user_id']);

        $hints = $query->getIndexHints();

        self::assertArrayHasKey('test_users', $hints);
        self::assertArrayHasKey('test_orders', $hints);
        self::assertSame('FORCE INDEX', $hints['test_users'][0]['type']);
        self::assertSame(['idx_email'], $hints['test_users'][0]['indexes']);
        self::assertSame('USE INDEX', $hints['test_orders'][0]['type']);
        self::assertSame(['idx_user_id'], $hints['test_orders'][0]['indexes']);
    }

    public function testFluentChaining(): void
    {
        $db = $this->getDb();
        $query = (new Query($db))
            ->from('test_users')
            ->forceIndex('test_users', 'idx_email')
            ->where(['is_active' => 1])
            ->orderBy('name')
            ->limit(3);

        $rows = $query->all();
        self::assertCount(3, $rows);
    }
}
