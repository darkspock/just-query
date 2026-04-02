<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Pgsql;

use FastPHP\QueryBuilder\Query\Query;

final class DMLTest extends PgsqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testInsertSingle(): void
    {
        $this->getDb()->createCommand()->insert('test_users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25,
        ])->execute();

        $row = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Test User'])
            ->one();

        self::assertNotNull($row);
        self::assertSame('test@example.com', $row['email']);
        self::assertSame(25, (int) $row['age']);
    }

    public function testInsertAndGetLastId(): void
    {
        $this->getDb()->createCommand()->insert('test_users', [
            'name' => 'First',
        ])->execute();

        $id1 = $this->getDb()->getLastInsertId('test_users_id_seq');

        $this->getDb()->createCommand()->insert('test_users', [
            'name' => 'Second',
        ])->execute();

        $id2 = $this->getDb()->getLastInsertId('test_users_id_seq');

        self::assertGreaterThan(0, (int) $id1);
        self::assertGreaterThan((int) $id1, (int) $id2);
    }

    public function testBatchInsert(): void
    {
        $this->getDb()->createCommand()->batchInsert(
            'test_users',
            ['name', 'email', 'age'],
            [
                ['User A', 'a@test.com', 20],
                ['User B', 'b@test.com', 30],
                ['User C', 'c@test.com', 40],
            ]
        )->execute();

        $count = (new Query($this->getDb()))->from('test_users')->count('*');
        self::assertSame(3, $count);
    }

    public function testUpdate(): void
    {
        $this->seedUsers();

        $this->getDb()->createCommand()->update(
            'test_users',
            ['name' => 'Alice Updated', 'age' => 31],
            ['name' => 'Alice']
        )->execute();

        $row = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Alice Updated'])
            ->one();

        self::assertNotNull($row);
        self::assertSame(31, (int) $row['age']);
    }

    public function testUpdateMultipleRows(): void
    {
        $this->seedUsers();

        $this->getDb()->createCommand()->update(
            'test_users',
            ['is_active' => false],
            ['>', 'age', 28]
        )->execute();

        $inactiveCount = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['is_active' => false])
            ->count('*');

        self::assertSame(3, $inactiveCount);
    }

    public function testDelete(): void
    {
        $this->seedUsers();

        $this->getDb()->createCommand()->delete('test_users', ['name' => 'Charlie'])->execute();

        $count = (new Query($this->getDb()))->from('test_users')->count('*');
        self::assertSame(4, $count);

        $deleted = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Charlie'])
            ->one();
        self::assertNull($deleted);
    }

    public function testDeleteWithOperator(): void
    {
        $this->seedUsers();

        $this->getDb()->createCommand()->delete('test_users', ['<', 'age', 25])->execute();

        $count = (new Query($this->getDb()))->from('test_users')->count('*');
        self::assertSame(4, $count);
    }

    public function testInsertWithNullValues(): void
    {
        $this->getDb()->createCommand()->insert('test_users', [
            'name' => 'Null Test',
            'email' => null,
            'config' => null,
        ])->execute();

        $row = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Null Test'])
            ->one();

        self::assertNull($row['email']);
        self::assertNull($row['config']);
    }

    public function testInsertWithJsonString(): void
    {
        $config = json_encode(['role' => 'admin', 'permissions' => ['read', 'write']]);

        $this->getDb()->createCommand()->insert('test_users', [
            'name' => 'Json Test',
            'config' => $config,
        ])->execute();

        $row = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Json Test'])
            ->one();

        $decoded = json_decode($row['config'], true);
        self::assertSame('admin', $decoded['role']);
        self::assertSame(['read', 'write'], $decoded['permissions']);
    }
}
